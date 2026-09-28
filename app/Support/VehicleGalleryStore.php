<?php

namespace App\Support;

use App\Models\Vehicle;
use App\Services\AdminVehicleIndexCache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * File-backed per-vehicle uploaded gallery (phase 1).
 *
 * Path is deterministic: storage/app/vehicle-galleries/{vehicle_id}.json
 * Never writes uploaded image rows to SQLite when enabled.
 */
final class VehicleGalleryStore
{
    public const ID_PREFIX = 'fg_';

    public static function isEnabled(): bool
    {
        return (bool) config('vehicle_gallery.file_store_enabled', true);
    }

    public static function isFileGalleryId(int|string|null $id): bool
    {
        return is_string($id) && str_starts_with($id, self::ID_PREFIX);
    }

    public static function pathFor(int|Vehicle $vehicle): string
    {
        $id = $vehicle instanceof Vehicle ? (int) $vehicle->id : (int) $vehicle;

        return rtrim(self::dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$id.'.json';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function images(int|Vehicle $vehicle): array
    {
        $payload = self::readPayload($vehicle);

        if ($payload === null) {
            return [];
        }

        $images = $payload['images'] ?? [];

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, static fn ($row) => is_array($row)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function formatted(int|Vehicle $vehicle): array
    {
        return array_map(
            static fn (array $row): array => self::format($row),
            self::images($vehicle),
        );
    }

    /**
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    public static function urlsByStage(int|Vehicle $vehicle): array
    {
        $stages = [
            'terminal' => [],
            'pickup' => [],
            'destination' => [],
        ];

        foreach (self::images($vehicle) as $image) {
            $stage = (string) ($image['stage'] ?? '');
            $url = (string) ($image['url'] ?? '');

            if (! isset($stages[$stage]) || $url === '') {
                continue;
            }

            if (! in_array($url, $stages[$stage], true)) {
                $stages[$stage][] = $url;
            }
        }

        return $stages;
    }

    /**
     * @param  array{
     *     stage: string,
     *     url: string,
     *     public_id?: ?string,
     *     original_name?: ?string,
     *     uploaded_by?: int|null,
     *     source?: string
     * }  $attributes
     * @return array<string, mixed>
     */
    public static function append(int|Vehicle $vehicle, array $attributes): array
    {
        $vehicleId = $vehicle instanceof Vehicle ? (int) $vehicle->id : (int) $vehicle;
        $payload = self::readPayload($vehicleId) ?? self::emptyPayload($vehicleId);

        $row = [
            'id' => self::ID_PREFIX.str_replace('-', '', (string) Str::uuid()),
            'stage' => (string) $attributes['stage'],
            'url' => (string) $attributes['url'],
            'public_id' => $attributes['public_id'] ?? null,
            'original_name' => $attributes['original_name'] ?? null,
            'uploaded_by' => isset($attributes['uploaded_by']) ? (int) $attributes['uploaded_by'] : null,
            'uploaded_at' => now()->toIso8601String(),
            'source' => (string) ($attributes['source'] ?? 'cloudinary'),
        ];

        $payload['images'][] = $row;
        $payload['updated_at'] = now()->toIso8601String();

        self::writePayload($vehicleId, $payload);
        AdminVehicleIndexCache::bumpVersion();

        return self::format($row);
    }

    /**
     * @return array<string, mixed>|null Removed image, or null when not found
     */
    public static function remove(int|Vehicle $vehicle, string $imageId): ?array
    {
        $vehicleId = $vehicle instanceof Vehicle ? (int) $vehicle->id : (int) $vehicle;
        $payload = self::readPayload($vehicleId);

        if ($payload === null) {
            return null;
        }

        $removed = null;
        $kept = [];

        foreach ($payload['images'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ((string) ($row['id'] ?? '') === $imageId) {
                $removed = $row;

                continue;
            }

            $kept[] = $row;
        }

        if ($removed === null) {
            return null;
        }

        $payload['images'] = $kept;
        $payload['updated_at'] = now()->toIso8601String();

        if ($kept === []) {
            self::deleteFile($vehicleId);
        } else {
            self::writePayload($vehicleId, $payload);
        }

        AdminVehicleIndexCache::bumpVersion();

        return self::format($removed);
    }

    public static function find(int|Vehicle $vehicle, string $imageId): ?array
    {
        foreach (self::images($vehicle) as $image) {
            if ((string) ($image['id'] ?? '') === $imageId) {
                return self::format($image);
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    public static function vehicleIdsWithImages(): array
    {
        $dir = self::dir();

        if (! File::isDirectory($dir)) {
            return [];
        }

        $ids = [];

        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();

            if (! preg_match('/^(\d+)\.json$/', $name, $m)) {
                continue;
            }

            if ($file->getSize() <= 0) {
                continue;
            }

            $images = self::images((int) $m[1]);

            if ($images !== []) {
                $ids[] = (int) $m[1];
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function format(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'stage' => (string) ($row['stage'] ?? ''),
            'url' => (string) ($row['url'] ?? ''),
            'original_name' => $row['original_name'] ?? null,
            'uploaded_at' => $row['uploaded_at'] ?? null,
            'source' => (string) ($row['source'] ?? 'cloudinary'),
            'public_id' => $row['public_id'] ?? null,
            'storage' => 'file',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function readPayload(int|Vehicle $vehicle): ?array
    {
        $path = self::pathFor($vehicle);

        if (! is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true);
        } catch (Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected static function writePayload(int $vehicleId, array $payload): void
    {
        $dir = self::dir();

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($json === false) {
            return;
        }

        $path = self::pathFor($vehicleId);
        $tmp = $path.'.tmp.'.getmypid();

        File::put($tmp, $json.PHP_EOL);

        // Atomic replace where supported.
        if (! @rename($tmp, $path)) {
            File::put($path, $json.PHP_EOL);
            @unlink($tmp);
        }
    }

    protected static function deleteFile(int $vehicleId): void
    {
        $path = self::pathFor($vehicleId);

        if (is_file($path)) {
            File::delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function emptyPayload(int $vehicleId): array
    {
        return [
            'version' => 1,
            'vehicle_id' => $vehicleId,
            'updated_at' => now()->toIso8601String(),
            'images' => [],
        ];
    }

    protected static function dir(): string
    {
        return (string) config('vehicle_gallery.path', storage_path('app/vehicle-galleries'));
    }
}
