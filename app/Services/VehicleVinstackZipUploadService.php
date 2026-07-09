<?php

namespace App\Services;

use App\Exceptions\GalleryTokenExpiredException;
use App\Models\Vehicle;
use App\Support\VehicleImageStages;
use Illuminate\Http\UploadedFile;
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
    ) {}

    /**
     * @return array{
     *     uploaded: int,
     *     failed: list<array{name: string, error: string}>,
     *     gallery: array<string, mixed>
     * }
     */
    public function uploadZip(Vehicle $vehicle, string $stage, UploadedFile $zip): array
    {
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
            } finally {
                if (is_file($entry['path'])) {
                    @unlink($entry['path']);
                }
            }
        }

        if ($uploaded === 0) {
            throw new RuntimeException(
                $failed[0]['error'] ?? 'vinstack_upload_failed',
            );
        }

        return [
            'uploaded' => $uploaded,
            'failed' => $failed,
            'gallery' => $this->gallery->buildGalleryPayload($vehicle->fresh() ?? $vehicle),
        ];
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

        return $message !== '' ? $message : 'vinstack_upload_failed';
    }
}
