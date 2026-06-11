<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\VehicleGalleryMerger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleImageDownloadService
{
    /**
     * @return list<string>
     */
    public function galleryUrls(Vehicle $vehicle): array
    {
        $merged = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

        $vinstackStages = VehicleGalleryMerger::resolveVinstackStages($merged, $vehicle);

        return VehicleGalleryMerger::flatten(
            VehicleGalleryMerger::merge($vinstackStages, $vehicle),
            $vehicle,
            $merged,
        );
    }

    public function urlBelongsToVehicle(Vehicle $vehicle, string $url): bool
    {
        if (in_array($url, $this->galleryUrls($vehicle), true)) {
            return true;
        }

        return $this->findUploadedImageByUrl($vehicle, $url) !== null;
    }

    public function download(Vehicle $vehicle, string $url): StreamedResponse|Response
    {
        $uploaded = $this->findUploadedImageByUrl($vehicle, $url);

        if ($uploaded !== null) {
            if ($uploaded->isCloudinary()) {
                return $this->proxy($uploaded->cloudinary_url);
            }

            return $this->streamLocal($uploaded);
        }

        return $this->proxy($url);
    }

    public function proxy(string $url): StreamedResponse|Response
    {
        $response = Http::timeout(45)
            ->withHeaders(['Accept' => 'image/*,*/*'])
            ->get($url);

        if (! $response->successful()) {
            abort(502, 'Unable to fetch image from source.');
        }

        $contentType = $response->header('Content-Type') ?: 'image/jpeg';

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    protected function streamLocal(VehicleUploadedImage $image): StreamedResponse
    {
        $disk = Storage::disk('public');

        if (! filled($image->path) || ! $disk->exists($image->path)) {
            abort(404, 'Image file not found.');
        }

        $mime = $disk->mimeType($image->path) ?: 'image/jpeg';

        return response()->stream(function () use ($disk, $image): void {
            $stream = $disk->readStream($image->path);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($image->original_name).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    protected function findUploadedImageByUrl(Vehicle $vehicle, string $url): ?VehicleUploadedImage
    {
        $vehicle->loadMissing('uploadedImages');

        foreach ($vehicle->uploadedImages as $image) {
            if ($image->publicUrl() === $url) {
                return $image;
            }

            if ($image->isCloudinary() && $image->cloudinary_url === $url) {
                return $image;
            }
        }

        $path = $this->pathFromPublicUrl($url);

        if ($path === null) {
            return null;
        }

        return $vehicle->uploadedImages->first(fn (VehicleUploadedImage $image) => $image->path === $path);
    }

    protected function pathFromPublicUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_contains($path, '/storage/')) {
            return null;
        }

        $relative = ltrim(Str::after($path, '/storage/'), '/');

        return $relative !== '' ? $relative : null;
    }
}
