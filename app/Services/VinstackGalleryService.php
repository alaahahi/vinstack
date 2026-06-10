<?php

namespace App\Services;

use App\Exceptions\GalleryTokenExpiredException;
use App\Models\VinstackSetting;
use App\Models\Vehicle;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleImageStages;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VinstackGalleryService
{
    public const DEFAULT_BASE_URL = 'https://app.vinstack.com/api/client-portal';

    public function __construct(
        protected VehicleUploadedImageService $uploadedImages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildGalleryPayload(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('uploadedImages');

        $vin = trim((string) $vehicle->vin);
        $galleryFresh = false;
        $galleryError = null;
        $galleryTokenExpired = false;
        $galleryStored = false;
        $galleryNewImagesCount = 0;

        $source = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

        if ($vin !== '') {
            try {
                $live = $this->fetchGallery($vin);
                $persisted = $this->persistGalleryImages($vehicle, $live);
                $galleryStored = $persisted['stored'];
                $galleryNewImagesCount = $persisted['new_count'];
                $vehicle->refresh();
                $source = is_array($vehicle->raw_data) ? $vehicle->raw_data : array_merge($source, $live);
                $galleryFresh = true;
            } catch (GalleryTokenExpiredException) {
                $galleryTokenExpired = true;
                $galleryError = 'gallery_token_expired';
            } catch (RuntimeException $e) {
                $galleryError = $e->getMessage();
            }
        }

        $vinstackStages = VehicleGalleryMerger::resolveVinstackStages($source, $vehicle);
        $imagesByStage = VehicleGalleryMerger::merge($vinstackStages, $vehicle);
        $images = VehicleGalleryMerger::flatten($imagesByStage, $vehicle, $source);

        $thumbnail = Arr::get($source, 'thumbnail_url');
        if (! is_string($thumbnail) || $thumbnail === '' || str_contains($thumbnail, 'no_photo.png')) {
            $thumbnail = $images[0] ?? null;
        }

        return [
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
            'images' => $images,
            'images_by_stage' => $imagesByStage,
            'uploaded_images' => $this->uploadedImages->listForVehicle($vehicle),
            'thumbnail_url' => $thumbnail,
            'gallery_fresh' => $galleryFresh,
            'gallery_error' => $galleryError,
            'gallery_token_expired' => $galleryTokenExpired,
            'gallery_stored' => $galleryStored,
            'gallery_new_images_count' => $galleryNewImagesCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $livePayload
     * @return array{stored: bool, new_count: int}
     */
    protected function persistGalleryImages(Vehicle $vehicle, array $livePayload): array
    {
        $existingRaw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $mergedSource = array_merge($existingRaw, $livePayload);

        $previousStages = VehicleGalleryMerger::resolveVinstackStages($existingRaw, $vehicle);
        $nextStages = VehicleGalleryMerger::resolveVinstackStages($mergedSource, $vehicle);

        $previousUrls = $this->flattenStageUrls($previousStages);
        $nextUrls = $this->flattenStageUrls($nextStages);
        $newUrls = array_values(array_diff($nextUrls, $previousUrls));

        if ($newUrls === []) {
            return ['stored' => false, 'new_count' => 0];
        }

        $rawData = $existingRaw;
        $rawData['images_by_stage'] = $nextStages;
        $rawData['images'] = $nextUrls;

        if (isset($livePayload['gallery'])) {
            $rawData['gallery'] = $livePayload['gallery'];
        } elseif (isset($livePayload['photos'])) {
            $rawData['gallery'] = $livePayload['photos'];
        }

        $thumbnail = Arr::get($livePayload, 'thumbnail_url') ?? Arr::get($mergedSource, 'thumbnail_url');
        if (is_string($thumbnail) && $thumbnail !== '' && ! str_contains($thumbnail, 'no_photo.png')) {
            $rawData['thumbnail_url'] = $thumbnail;
        }

        $rawData['gallery_synced_at'] = now()->toIso8601String();

        $vehicle->update([
            'images' => $nextUrls,
            'raw_data' => $rawData,
        ]);

        return [
            'stored' => true,
            'new_count' => count($newUrls),
        ];
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     * @return list<string>
     */
    protected function flattenStageUrls(array $stages): array
    {
        $flat = [];

        foreach (VehicleImageStages::STAGES as $stage) {
            foreach ($stages[$stage] ?? [] as $url) {
                if (! in_array($url, $flat, true)) {
                    $flat[] = $url;
                }
            }
        }

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchGallery(string $vin): array
    {
        $json = $this->request('get', '/autos/'.rawurlencode($vin).'/gallery');

        if (isset($json['data']) && is_array($json['data'])) {
            return $this->normalizeGalleryPayload($json['data']);
        }

        return $this->normalizeGalleryPayload(is_array($json) ? $json : []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeGalleryPayload(array $payload): array
    {
        if (isset($payload['gallery']) && is_array($payload['gallery'])) {
            return ['gallery' => $payload['gallery'], ...$payload];
        }

        if (isset($payload['photos']) && is_array($payload['photos'])) {
            return ['photos' => $payload['photos'], ...$payload];
        }

        if (isset($payload['images']) && is_array($payload['images'])) {
            return $payload;
        }

        if ($this->looksLikeImageList($payload)) {
            return ['images' => array_values($payload)];
        }

        return $payload;
    }

    /**
     * @param  array<mixed>  $payload
     */
    protected function looksLikeImageList(array $payload): bool
    {
        if ($payload === []) {
            return false;
        }

        foreach ($payload as $item) {
            if (is_string($item)) {
                continue;
            }

            if (is_array($item) && isset($item['url'])) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path): array
    {
        /** @var Response $response */
        $response = $this->client()->{$method}($path);

        if ($response->status() === 401) {
            $this->markGalleryTokenExpired();

            throw new GalleryTokenExpiredException;
        }

        if ($response->failed()) {
            $message = $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            throw new RuntimeException(
                "Gallery API error ({$response->status()}): {$message}"
            );
        }

        $this->markGalleryTokenValid();

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    protected function client(): PendingRequest
    {
        $settings = VinstackSetting::current();

        $baseUrl = $settings->gallery_api_base_url
            ?: config('services.vinstack.gallery_base_url')
            ?: self::DEFAULT_BASE_URL;

        $token = $settings->gallery_api_token ?: config('services.vinstack.gallery_token');

        if (! $token) {
            throw new RuntimeException('Gallery API token is not configured.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken(trim($token))
            ->acceptJson()
            ->timeout(60);
    }

    protected function markGalleryTokenExpired(): void
    {
        VinstackSetting::current()->update([
            'gallery_token_expired' => true,
            'gallery_token_checked_at' => now(),
        ]);
    }

    protected function markGalleryTokenValid(): void
    {
        $settings = VinstackSetting::current();

        if ($settings->gallery_token_expired) {
            $settings->update([
                'gallery_token_expired' => false,
                'gallery_token_checked_at' => now(),
            ]);

            return;
        }

        $settings->update(['gallery_token_checked_at' => now()]);
    }
}
