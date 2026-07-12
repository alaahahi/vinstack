<?php

namespace App\Services;

use App\Models\Dealer;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class ContainerZipUploadService
{
    public const MAX_ZIP_KILOBYTES = 153600;

    public const MAX_IMAGES_PER_ZIP = 200;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];

    public function __construct(
        protected ContainerImageService $images,
        protected ContainerService $containers,
    ) {}

    /**
     * @return array{
     *     images: list<array<string, mixed>>,
     *     byVin: array<string, list<string>>,
     *     unmatched: list<string>,
     *     meta: array<string, mixed>,
     *     uploaded: int,
     *     failed: list<array{index?: int, name?: string, error?: string}>
     * }
     */
    public function uploadZip(string $container, UploadedFile $zip, ?Dealer $dealer = null, bool $replace = true): array
    {
        $entries = $this->extractImageEntries($zip);

        try {
            $detail = $this->containers->vehiclesForContainer($container, $dealer);
            $vehicles = $detail['vehicles'] ?? [];
            $metadata = [];

            foreach ($entries as $index => $entry) {
                $match = $this->matchImageToVehicle($entry['name'], $vehicles, $index);
                $metadata[] = [
                    'name' => $entry['name'],
                    'vin' => $match['vin'],
                    'lot' => $match['lot'],
                ];
            }

            return $this->images->uploadFromPaths($container, $entries, $metadata, $replace);
        } finally {
            $this->cleanupEntries($entries);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $vehicles
     * @return array{vin: ?string, lot: ?string}
     */
    public function matchImageToVehicle(string $filename, array $vehicles, int $sequentialIndex): array
    {
        $base = pathinfo($this->basename($filename), PATHINFO_FILENAME);
        $upper = strtoupper($base);

        foreach ($vehicles as $vehicle) {
            $vin = $this->normalizeVin($vehicle['vin'] ?? null);

            if ($vin !== '' && (str_contains($upper, $vin) || str_contains($vin, $upper))) {
                return [
                    'vin' => $vin,
                    'lot' => $this->normalizeLot($vehicle['lot'] ?? $vehicle['raw_data']['lot'] ?? null),
                ];
            }
        }

        foreach ($vehicles as $vehicle) {
            $vin = $this->normalizeVin($vehicle['vin'] ?? null);

            if (strlen($vin) >= 6) {
                $suffix = substr($vin, -6);

                if (str_contains($upper, $suffix)) {
                    return [
                        'vin' => $vin,
                        'lot' => $this->normalizeLot($vehicle['lot'] ?? $vehicle['raw_data']['lot'] ?? null),
                    ];
                }
            }
        }

        foreach ($vehicles as $vehicle) {
            $lot = $this->normalizeLot($vehicle['lot'] ?? $vehicle['raw_data']['lot'] ?? null);

            if ($lot !== '' && (str_contains($upper, $lot) || preg_match('/\b'.preg_quote($lot, '/').'\b/', $base))) {
                return [
                    'vin' => $this->normalizeVin($vehicle['vin'] ?? null) ?: null,
                    'lot' => $lot,
                ];
            }
        }

        if (isset($vehicles[$sequentialIndex])) {
            $vehicle = $vehicles[$sequentialIndex];

            return [
                'vin' => $this->normalizeVin($vehicle['vin'] ?? null) ?: null,
                'lot' => $this->normalizeLot($vehicle['lot'] ?? $vehicle['raw_data']['lot'] ?? null),
            ];
        }

        return ['vin' => null, 'lot' => null];
    }

    /**
     * Extract ZIP images into a persistent staging directory (for background transfer).
     *
     * @return list<array{name: string, path: string}>
     */
    public function extractToStagingDirectory(string $zipPath, string $directory): array
    {
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('zip_extract_failed');
        }

        return $this->extractImageEntriesFromPath($zipPath, $directory);
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    protected function extractImageEntries(UploadedFile $zip): array
    {
        $zipPath = $zip->getRealPath();

        if (! is_string($zipPath) || $zipPath === '') {
            throw new RuntimeException('invalid_zip');
        }

        return $this->extractImageEntriesFromPath($zipPath, null);
    }

    /**
     * @return list<array{name: string, path: string}>
     */
    protected function extractImageEntriesFromPath(string $zipPath, ?string $targetDirectory): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('zip_extension_missing');
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

            if ($targetDirectory !== null) {
                $target = rtrim($targetDirectory, DIRECTORY_SEPARATOR)
                    .DIRECTORY_SEPARATOR
                    .sprintf('%04d_%s', $index, $basename);
            } else {
                $tempPath = tempnam(sys_get_temp_dir(), 'container-zip-');

                if ($tempPath === false) {
                    $archive->close();

                    throw new RuntimeException('zip_extract_failed');
                }

                $target = $tempPath.'.'.$this->extension($basename);
                @unlink($tempPath);
            }

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

        if ($entries === []) {
            throw new RuntimeException('zip_no_images');
        }

        if (count($entries) > self::MAX_IMAGES_PER_ZIP) {
            $this->cleanupEntries($entries);

            throw new RuntimeException('zip_too_many_images');
        }

        return $entries;
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

    protected function isAllowedImageName(string $path): bool
    {
        return in_array($this->extension($this->basename($path)), self::ALLOWED_EXTENSIONS, true);
    }

    protected function basename(string $path): string
    {
        $parts = preg_split('#[/\\\\]#', $path) ?: [];

        return (string) (end($parts) ?: $path);
    }

    protected function extension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    protected function normalizeVin(?string $vin): string
    {
        if ($vin === null || trim($vin) === '') {
            return '';
        }

        return strtoupper(trim($vin));
    }

    protected function normalizeLot(?string $lot): string
    {
        if ($lot === null || trim($lot) === '') {
            return '';
        }

        return ltrim(trim($lot), '#');
    }
}
