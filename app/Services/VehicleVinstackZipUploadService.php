<?php

namespace App\Services;

use App\Exceptions\GalleryTokenExpiredException;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\UploadLimits;
use App\Support\VehicleImageStages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class VehicleVinstackZipUploadService
{
    public const MAX_ZIP_KILOBYTES = 51200;

    public const MAX_IMAGES_PER_ZIP = 100;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];

    public function __construct(
        protected VinstackGalleryService $gallery,
        protected VehicleUploadedImageService $uploadedImages,
        protected CloudinaryService $cloudinary,
    ) {}

    /**
     * @return array{
     *     uploaded: int,
     *     failed: list<array{name: string, error: string}>,
     *     gallery: array<string, mixed>,
     *     mode: 'vinstack'|'cloudinary'
     * }
     */
    public function uploadZip(Vehicle $vehicle, string $stage, UploadedFile $zip, ?User $user = null): array
    {
        UploadLimits::extendExecutionTime();

        if (! in_array($stage, VehicleImageStages::STAGES, true)) {
            throw new RuntimeException('invalid_stage');
        }

        if ($this->gallery->resolveGalleryIdentifiers($vehicle) === []) {
            throw new RuntimeException('gallery_vehicle_id_missing');
        }

        $entries = $this->extractImageEntries($zip);

        if ($entries === []) {
            throw new RuntimeException('zip_no_images');
        }

        try {
            $vinstackUploaded = 0;
            $vinstackFailed = [];
            $cloudinaryUploaded = 0;
            $cloudinaryFailed = [];

            // Bulk ZIP uploads are slow through the live Vinstack gallery API (one HTTP call per image).
            // Prefer Cloudinary when configured to avoid proxy timeouts on large archives.
            if ($user && $this->cloudinary->isConfigured()) {
                [$cloudinaryUploaded, $cloudinaryFailed] = $this->uploadEntriesToCloudinary(
                    $vehicle,
                    $stage,
                    $entries,
                    $user,
                );

                if ($cloudinaryUploaded > 0) {
                    Log::info('vehicle.zip_upload.cloudinary_direct', [
                        'vehicle_id' => $vehicle->id,
                        'stage' => $stage,
                        'uploaded' => $cloudinaryUploaded,
                        'failed' => count($cloudinaryFailed),
                    ]);

                    return $this->buildResult($vehicle, $cloudinaryUploaded, $cloudinaryFailed, 'cloudinary');
                }
            }

            if ($this->gallery->usesLiveGalleryApi($vehicle)) {
                [$vinstackUploaded, $vinstackFailed] = $this->uploadEntriesToVinstack($vehicle, $stage, $entries);
            } elseif ($user && $this->cloudinary->isConfigured()) {
                $firstError = $cloudinaryFailed[0]['error'] ?? 'cloudinary_upload_failed';

                throw new RuntimeException($firstError);
            }

            if ($vinstackUploaded > 0) {
                return $this->buildResult($vehicle, $vinstackUploaded, $vinstackFailed, 'vinstack');
            }

            if ($user && $this->cloudinary->isConfigured()) {
                if ($cloudinaryUploaded === 0 && $cloudinaryFailed === []) {
                    [$cloudinaryUploaded, $cloudinaryFailed] = $this->uploadEntriesToCloudinary(
                        $vehicle,
                        $stage,
                        $entries,
                        $user,
                    );
                }

                if ($cloudinaryUploaded > 0) {
                    Log::info('vehicle.zip_upload.cloudinary_fallback', [
                        'vehicle_id' => $vehicle->id,
                        'stage' => $stage,
                        'uploaded' => $cloudinaryUploaded,
                        'failed' => count($cloudinaryFailed),
                        'vinstack_error' => $this->sanitizeErrorMessage($vinstackFailed[0]['error'] ?? ''),
                    ]);

                    return $this->buildResult($vehicle, $cloudinaryUploaded, $cloudinaryFailed, 'cloudinary');
                }

                $failed = $cloudinaryFailed;
            } else {
                $failed = $vinstackFailed;
            }

            $firstError = $this->sanitizeErrorMessage($failed[0]['error'] ?? 'vinstack_upload_failed');

            Log::warning('vehicle.zip_upload.failed', [
                'vehicle_id' => $vehicle->id,
                'vin' => $vehicle->vin,
                'stage' => $stage,
                'image_count' => count($entries),
                'error' => $firstError,
                'cloudinary_configured' => $this->cloudinary->isConfigured(),
            ]);

            if (! $this->cloudinary->isConfigured()) {
                throw new RuntimeException($firstError.'|cloudinary_not_configured');
            }

            throw new RuntimeException($firstError);
        } finally {
            $this->cleanupEntries($entries);
        }
    }

    /**
     * @param  list<array{name: string, path: string}>  $entries
     * @return array{0: int, 1: list<array{name: string, error: string}>}
     */
    protected function uploadEntriesToVinstack(Vehicle $vehicle, string $stage, array $entries): array
    {
        $uploaded = 0;
        $failed = [];

        foreach ($entries as $entry) {
            try {
                $this->gallery->uploadStageImage($vehicle, $stage, $entry['path'], $entry['name']);
                $uploaded++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'name' => $entry['name'],
                    'error' => $this->humanizeUploadError($e),
                ];
            }
        }

        return [$uploaded, $failed];
    }

    /**
     * @param  list<array{name: string, path: string}>  $entries
     * @return array{0: int, 1: list<array{name: string, error: string}>}
     */
    protected function uploadEntriesToCloudinary(Vehicle $vehicle, string $stage, array $entries, User $user): array
    {
        $uploaded = 0;
        $failed = [];

        foreach ($entries as $entry) {
            try {
                $this->uploadedImages->storeFromPath($vehicle, $stage, $entry['path'], $entry['name'], $user);
                $uploaded++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'name' => $entry['name'],
                    'error' => trim($e->getMessage()) ?: 'cloudinary_upload_failed',
                ];
            }
        }

        return [$uploaded, $failed];
    }

    /**
     * @param  list<array{name: string, error: string}>  $failed
     * @return array{
     *     uploaded: int,
     *     failed: list<array{name: string, error: string}>,
     *     gallery: array<string, mixed>,
     *     mode: 'vinstack'|'cloudinary'
     * }
     */
    protected function buildResult(Vehicle $vehicle, int $uploaded, array $failed, string $mode): array
    {
        return [
            'uploaded' => $uploaded,
            'failed' => $failed,
            'gallery' => $this->gallery->buildGalleryPayload($vehicle->fresh() ?? $vehicle),
            'mode' => $mode,
        ];
    }

    /**
     * @param  list<array{name: string, path: string}>  $entries
     */
    protected function cleanupEntries(array $entries): void
    {
        foreach ($entries as $entry) {
            if (is_file($entry['path'])) {
                @unlink($entry['path']);
            }
        }
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    protected function extractImageEntries(UploadedFile $zip): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('zip_extension_missing');
        }

        $zipPath = $zip->getRealPath();

        if (! is_string($zipPath) || $zipPath === '') {
            throw new RuntimeException('invalid_zip');
        }

        $archive = new ZipArchive;
        $opened = $archive->open($zipPath);

        if ($opened !== true) {
            throw new RuntimeException('invalid_zip');
        }

        $entries = [];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);

            if (! is_array($stat)) {
                continue;
            }

            $name = (string) ($stat['name'] ?? '');

            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            if (! $this->isAllowedImageName($name)) {
                continue;
            }

            $basename = $this->basename($name);
            $tempPath = tempnam(sys_get_temp_dir(), 'vinstack-zip-');

            if ($tempPath === false) {
                $archive->close();

                throw new RuntimeException('zip_extract_failed');
            }

            $target = $tempPath.'.'.$this->extension($basename);
            @unlink($tempPath);

            $stream = $archive->getStream($name);

            if ($stream === false) {
                continue;
            }

            $bytes = stream_get_contents($stream);
            fclose($stream);

            if ($bytes === false || $bytes === '') {
                continue;
            }

            file_put_contents($target, $bytes);

            $entries[] = [
                'name' => $basename,
                'path' => $target,
            ];
        }

        $archive->close();

        usort($entries, fn (array $left, array $right) => strnatcasecmp($left['name'], $right['name']));

        if (count($entries) > self::MAX_IMAGES_PER_ZIP) {
            foreach ($entries as $entry) {
                if (is_file($entry['path'])) {
                    @unlink($entry['path']);
                }
            }

            throw new RuntimeException('zip_too_many_images');
        }

        return $entries;
    }

    protected function isAllowedImageName(string $path): bool
    {
        $extension = $this->extension($this->basename($path));

        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    protected function basename(string $path): string
    {
        $parts = preg_split('#[/\\\\]#', $path) ?: [];

        return (string) (array_pop($parts) ?: $path);
    }

    protected function extension(string $filename): string
    {
        $match = [];

        if (preg_match('/\.([a-z0-9]+)$/i', $filename, $match) !== 1) {
            return '';
        }

        return strtolower($match[1]);
    }

    protected function humanizeUploadError(\Throwable $e): string
    {
        if ($e instanceof GalleryTokenExpiredException) {
            return 'gallery_token_expired';
        }

        $message = trim($e->getMessage());

        return $this->sanitizeErrorMessage($message !== '' ? $message : 'vinstack_upload_failed');
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        if (str_contains($message, '<!DOCTYPE') || str_contains($message, '<html')) {
            if (preg_match('/<pre>(.*?)<\/pre>/is', $message, $match) === 1) {
                return trim(html_entity_decode(strip_tags($match[1])));
            }

            if (preg_match('/Gallery API error \((\d+)\)/', $message, $match) === 1) {
                return 'Gallery API error ('.$match[1].'): endpoint not found';
            }

            return 'Gallery API error: invalid response';
        }

        if (strlen($message) > 220) {
            return substr($message, 0, 220).'…';
        }

        return $message;
    }
}
