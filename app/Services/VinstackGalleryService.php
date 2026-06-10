<?php

namespace App\Services;

use App\Enums\VehicleSource;
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

        if ($vin !== '' && $this->usesLiveGalleryApi($vehicle)) {
            try {
                $live = $this->fetchGallery($vin);
                $persisted = $this->persistGalleryImages($vehicle, $live);
                $galleryStored = $persisted['stored'];
                $galleryNewImagesCount = $persisted['new_count'];
                $vehicle->refresh();
                $source = array_merge(
                    is_array($vehicle->raw_data) ? $vehicle->raw_data : [],
                    $live,
                );
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
            'gallery' => Arr::get($source, 'gallery'),
            'terminal' => Arr::get($source, 'terminal'),
            'pickup' => Arr::get($source, 'pickup'),
            'destination' => Arr::get($source, 'destination'),
            'gallery_fresh' => $galleryFresh,
            'gallery_error' => $galleryError,
            'gallery_token_expired' => $galleryTokenExpired,
            'gallery_stored' => $galleryStored,
            'gallery_new_images_count' => $galleryNewImagesCount,
            'gallery_api_applicable' => $this->usesLiveGalleryApi($vehicle),
        ];
    }

    public function usesLiveGalleryApi(Vehicle $vehicle): bool
    {
        return $vehicle->source === VehicleSource::Vinstack;
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
        $stagesChanged = $this->stagesChanged($previousStages, $nextStages);
        $fromClientPortal = $this->isClientPortalGalleryPayload($livePayload);

        if ($newUrls === [] && ! ($fromClientPortal && $stagesChanged)) {
            return ['stored' => false, 'new_count' => 0];
        }

        $rawData = array_merge($existingRaw, $livePayload);
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
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $before
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $after
     */
    protected function stagesChanged(array $before, array $after): bool
    {
        foreach (VehicleImageStages::STAGES as $stage) {
            if (count($before[$stage] ?? []) !== count($after[$stage] ?? [])) {
                return true;
            }
        }

        return false;
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
     * @return array<string, mixed>|null
     */
    public function fetchContainerTrack(string $containerId): ?array
    {
        $credentials = $this->resolveCredentials();

        if ($credentials['token'] === '') {
            return null;
        }

        /** @var Response $response */
        $response = $this->client()->get('/containers/'.rawurlencode($containerId).'/track');

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 401) {
            $this->markGalleryTokenExpired();

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $this->markGalleryTokenValid();

        $json = $response->json();

        return is_array($json) && $json !== [] ? $json : null;
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
        $normalized = $payload;

        if ($this->isClientPortalGalleryPayload($payload)) {
            $normalized['gallery'] = $payload;
            $normalized['images_by_stage'] = $this->stageUrlsFromClientPortalPayload($payload);

            if (! isset($normalized['images']) && isset($payload['urls']) && is_array($payload['urls'])) {
                $normalized['images'] = $payload['urls'];
            }
        }

        if (isset($normalized['gallery']) && is_array($normalized['gallery'])) {
            return ['gallery' => $normalized['gallery'], ...$normalized];
        }

        if (isset($normalized['photos']) && is_array($normalized['photos'])) {
            return ['photos' => $normalized['photos'], ...$normalized];
        }

        if (isset($normalized['images']) && is_array($normalized['images'])) {
            return $normalized;
        }

        if ($this->looksLikeImageList($normalized)) {
            return ['images' => array_values($normalized)];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function isClientPortalGalleryPayload(array $payload): bool
    {
        foreach (VehicleImageStages::STAGES as $stage) {
            $block = $payload[$stage] ?? null;

            if (is_array($block) && isset($block['urls']) && is_array($block['urls'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    protected function stageUrlsFromClientPortalPayload(array $payload): array
    {
        $stages = [
            'terminal' => [],
            'pickup' => [],
            'destination' => [],
        ];

        foreach (VehicleImageStages::STAGES as $stage) {
            $block = $payload[$stage] ?? null;

            if (! is_array($block)) {
                continue;
            }

            $urls = $block['urls'] ?? [];

            if (! is_array($urls)) {
                continue;
            }

            $stages[$stage] = array_values(array_filter(
                $urls,
                fn ($url) => is_string($url) && $url !== '' && ! str_contains($url, 'no_photo'),
            ));
        }

        return $stages;
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

    /**
     * @return array{base_url: string, token: string, token_source: string}
     */
    public function resolveCredentials(): array
    {
        $settings = VinstackSetting::current();

        $baseUrl = $settings->gallery_api_base_url
            ?: config('services.vinstack.gallery_base_url')
            ?: self::DEFAULT_BASE_URL;

        if ($settings->gallery_api_token) {
            $token = $settings->gallery_api_token;
            $tokenSource = 'gallery';
        } elseif ($settings->api_token) {
            $token = $settings->api_token;
            $tokenSource = 'sync';
        } elseif (config('services.vinstack.gallery_token')) {
            $token = config('services.vinstack.gallery_token');
            $tokenSource = 'env_gallery';
        } elseif (config('services.vinstack.token')) {
            $token = config('services.vinstack.token');
            $tokenSource = 'env_sync';
        } else {
            $token = '';
            $tokenSource = 'none';
        }

        return [
            'base_url' => rtrim($baseUrl, '/'),
            'token' => trim((string) $token),
            'token_source' => $tokenSource,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function probeSettings(): array
    {
        $credentials = $this->resolveCredentials();
        $exampleVin = '1HGBH41JXMN109186';

        if ($credentials['token'] === '') {
            return [
                'ok' => false,
                'message' => 'لم يُضبط توكن المعرض — أدخل Gallery Token أو تأكد من توكن المزامنة.',
                'base_url' => $credentials['base_url'],
                'endpoint_example' => $credentials['base_url'].'/autos/'.$exampleVin.'/gallery',
                'has_token' => false,
                'token_source' => $credentials['token_source'],
            ];
        }

        return [
            'ok' => true,
            'message' => 'الإعدادات جاهزة — سيُستدعى المعرض عند فتح صور السيارة.',
            'base_url' => $credentials['base_url'],
            'endpoint_example' => $credentials['base_url'].'/autos/'.$exampleVin.'/gallery',
            'has_token' => true,
            'token_source' => $credentials['token_source'],
        ];
    }

    protected function client(): PendingRequest
    {
        $credentials = $this->resolveCredentials();

        if ($credentials['token'] === '') {
            throw new RuntimeException('gallery_token_missing');
        }

        return Http::baseUrl($credentials['base_url'])
            ->withToken($credentials['token'])
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
