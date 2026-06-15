<?php

namespace App\Support;

use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\VehicleRawDataLocations;
use Illuminate\Support\Arr;

class VehicleGalleryMerger
{
    /**
     * @param  array<string, mixed>  $source
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    public static function resolveVinstackStages(array $source, Vehicle $vehicle): array
    {
        $merged = $source;

        $fromRaw = Arr::get($merged, 'images', []);
        $fromColumn = is_array($vehicle->images) ? $vehicle->images : [];

        if ($fromRaw !== [] || $fromColumn !== []) {
            $merged['images'] = array_values(array_unique([
                ...(is_array($fromRaw) ? $fromRaw : []),
                ...$fromColumn,
            ], SORT_REGULAR));
        }

        return VehicleImageStages::resolve($merged);
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $vinstackStages
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    public static function merge(array $vinstackStages, Vehicle $vehicle): array
    {
        $stages = [
            'terminal' => [...($vinstackStages['terminal'] ?? [])],
            'pickup' => [...($vinstackStages['pickup'] ?? [])],
            'destination' => [...($vinstackStages['destination'] ?? [])],
        ];

        $vehicle->loadMissing('uploadedImages');

        foreach ($vehicle->uploadedImages as $image) {
            if (! VehicleUploadedImage::isValidStage($image->stage)) {
                continue;
            }

            $url = $image->publicUrl();

            if ($url === '' || self::containsUrl($stages, $url)) {
                continue;
            }

            $stages[$image->stage][] = $url;
        }

        foreach (VehicleImageStages::STAGES as $stage) {
            $stages[$stage] = array_values(array_unique($stages[$stage]));
        }

        return $stages;
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $imagesByStage
     * @param  array<string, mixed>  $merged
     * @return list<string>
     */
    public static function flatten(array $imagesByStage, Vehicle $vehicle, array $merged): array
    {
        $flat = [];

        foreach (VehicleImageStages::STAGES as $stage) {
            foreach ($imagesByStage[$stage] ?? [] as $url) {
                if (! in_array($url, $flat, true)) {
                    $flat[] = $url;
                }
            }
        }

        if ($flat !== []) {
            return $flat;
        }

        $fromMerged = collect(Arr::get($merged, 'images', []))
            ->filter(fn ($url) => is_string($url) && $url !== '' && ! str_contains($url, 'no_photo.png'))
            ->values()
            ->all();

        if ($fromMerged !== []) {
            return $fromMerged;
        }

        $fromVehicle = is_array($vehicle->images) ? $vehicle->images : [];

        return array_values(array_filter(
            $fromVehicle,
            fn ($url) => is_string($url) && $url !== '' && ! str_contains($url, 'no_photo.png'),
        ));
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     */
    protected static function containsUrl(array $stages, string $url): bool
    {
        foreach (VehicleImageStages::STAGES as $stage) {
            if (in_array($url, $stages[$stage], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge sync payload images with existing vehicle gallery data (additive only).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function mergeSyncPayload(Vehicle $vehicle, array $payload): array
    {
        $existingRaw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $newRaw = is_array($payload['raw_data'] ?? null) ? $payload['raw_data'] : [];

        $existingStages = self::resolveVinstackStages($existingRaw, $vehicle);
        self::appendPersistedStageImages($existingStages, $existingRaw, $vehicle);
        $newStages = VehicleImageStages::resolve($newRaw);

        $mergedStages = [];

        foreach (VehicleImageStages::STAGES as $stage) {
            $mergedStages[$stage] = self::unionUrlLists(
                $existingStages[$stage] ?? [],
                $newStages[$stage] ?? [],
            );
        }

        $mergedImages = self::flatten($mergedStages, $vehicle, ['images' => []]);

        if ($mergedImages === []) {
            $mergedImages = self::unionUrlLists(
                is_array($vehicle->images) ? $vehicle->images : [],
                is_array($payload['images'] ?? null) ? $payload['images'] : [],
            );
        }

        $protectedKeys = [
            'images',
            'images_by_stage',
            'gallery',
            'photos',
            'thumbnail_url',
            'gallery_synced_at',
            ...VehicleImageStages::STAGES,
        ];

        $mergedRaw = array_merge($existingRaw, Arr::except($newRaw, $protectedKeys));
        $mergedRaw['images'] = $mergedImages;
        $mergedRaw['images_by_stage'] = $mergedStages;

        $hasGalleryStructure = is_array(Arr::get($existingRaw, 'gallery'))
            || is_array(Arr::get($newRaw, 'gallery'))
            || (isset($existingRaw['photos']) && is_array($existingRaw['photos']));

        if (! $hasGalleryStructure) {
            foreach (VehicleImageStages::STAGES as $stage) {
                $mergedRaw[$stage] = self::mergeRawStageBlock(
                    Arr::get($existingRaw, $stage),
                    Arr::get($newRaw, $stage),
                    $mergedStages[$stage],
                );
            }
        } else {
            foreach (VehicleImageStages::STAGES as $stage) {
                $existing = Arr::get($existingRaw, $stage);

                if (is_string($existing) && $existing !== '') {
                    $mergedRaw[$stage] = $existing;
                } else {
                    unset($mergedRaw[$stage]);
                }
            }
        }

        $mergedRaw['gallery'] = self::mergeRawGalleryNode(
            Arr::get($existingRaw, 'gallery'),
            Arr::get($newRaw, 'gallery'),
            $mergedStages,
        );

        if (isset($existingRaw['photos']) && is_array($existingRaw['photos'])) {
            $mergedRaw['photos'] = self::mergeRawGalleryNode(
                $existingRaw['photos'],
                is_array($newRaw['photos'] ?? null) ? $newRaw['photos'] : null,
                $mergedStages,
            );
        }

        $mergedRaw['thumbnail_url'] = self::pickThumbnail(
            Arr::get($existingRaw, 'thumbnail_url'),
            Arr::get($newRaw, 'thumbnail_url'),
            $mergedImages,
        );

        if (isset($existingRaw['gallery_synced_at'])) {
            $mergedRaw['gallery_synced_at'] = $existingRaw['gallery_synced_at'];
        }

        $payload['images'] = $mergedImages;
        $payload['raw_data'] = VehicleRawDataLocations::sanitizeForList($mergedRaw);

        return $payload;
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     * @param  array<string, mixed>  $existingRaw
     */
    protected static function appendPersistedStageImages(array &$stages, array $existingRaw, Vehicle $vehicle): void
    {
        $persistedStages = Arr::get($existingRaw, 'images_by_stage', []);

        if (is_array($persistedStages)) {
            foreach (VehicleImageStages::STAGES as $stage) {
                if (! is_array($persistedStages[$stage] ?? null)) {
                    continue;
                }

                $stages[$stage] = self::unionUrlLists(
                    $stages[$stage] ?? [],
                    $persistedStages[$stage],
                );
            }
        }

        $flatExisting = self::unionUrlLists(
            is_array($vehicle->images) ? $vehicle->images : [],
            is_array(Arr::get($existingRaw, 'images')) ? Arr::get($existingRaw, 'images') : [],
        );

        foreach ($flatExisting as $url) {
            if (self::containsUrl($stages, $url)) {
                continue;
            }

            $stages['terminal'] = self::unionUrlLists($stages['terminal'] ?? [], [$url]);
        }
    }

    /**
     * @param  list<string>  ...$lists
     * @return list<string>
     */
    protected static function unionUrlLists(array ...$lists): array
    {
        $flat = [];

        foreach ($lists as $list) {
            foreach ($list as $url) {
                if (! is_string($url) || $url === '' || str_contains($url, 'no_photo')) {
                    continue;
                }

                if (! in_array($url, $flat, true)) {
                    $flat[] = $url;
                }
            }
        }

        return $flat;
    }

    /**
     * @param  list<string>  $mergedUrls
     */
    protected static function mergeRawStageBlock(mixed $existing, mixed $incoming, array $mergedUrls): mixed
    {
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        if ($mergedUrls === []) {
            return $existing ?? $incoming;
        }

        $existingKeys = is_array($existing) && is_array($existing['keys'] ?? null)
            ? $existing['keys']
            : [];

        return [
            'urls' => $mergedUrls,
            'keys' => self::mergeBlockKeys($existingKeys, count($mergedUrls)),
        ];
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $mergedStages
     */
    protected static function mergeRawGalleryNode(mixed $existing, mixed $incoming, array $mergedStages): mixed
    {
        if (! is_array($existing) && ! is_array($incoming)) {
            return $existing ?? $incoming;
        }

        $merged = is_array($existing) ? $existing : [];

        if (is_array($incoming)) {
            foreach ($incoming as $key => $value) {
                if (! array_key_exists($key, $merged)) {
                    $merged[$key] = $value;
                }
            }
        }

        foreach (VehicleImageStages::STAGES as $stage) {
            if (($mergedStages[$stage] ?? []) === []) {
                continue;
            }

            if (isset($merged[$stage]) && is_array($merged[$stage])) {
                $merged[$stage] = self::mergeRawStageBlock(
                    $merged[$stage],
                    is_array($incoming[$stage] ?? null) ? $incoming[$stage] : null,
                    $mergedStages[$stage],
                );
            } else {
                $merged[$stage] = [
                    'urls' => $mergedStages[$stage],
                    'keys' => array_fill(0, count($mergedStages[$stage]), null),
                ];
            }
        }

        return $merged;
    }

    /**
     * @param  list<mixed>  $existingKeys
     * @return list<mixed>
     */
    protected static function mergeBlockKeys(array $existingKeys, int $urlCount): array
    {
        if ($urlCount === 0) {
            return [];
        }

        $keys = array_slice($existingKeys, 0, $urlCount);

        while (count($keys) < $urlCount) {
            $keys[] = null;
        }

        return $keys;
    }

    /**
     * @param  list<string>  $mergedImages
     */
    protected static function pickThumbnail(mixed $existingThumb, mixed $newThumb, array $mergedImages): ?string
    {
        foreach ([$existingThumb, $newThumb] as $candidate) {
            if (is_string($candidate) && $candidate !== '' && ! str_contains($candidate, 'no_photo.png')) {
                return $candidate;
            }
        }

        return $mergedImages[0] ?? null;
    }
}
