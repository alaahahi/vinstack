<?php

namespace App\Support;

use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
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
}
