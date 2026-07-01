<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleImageStages;

class VehicleGalleryOrderService
{
    /**
     * Persist a new URL order for one gallery stage (Vinstack + admin uploads).
     *
     * @param  list<string>  $orderedUrls
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    public function reorderStage(Vehicle $vehicle, string $stage, array $orderedUrls): array
    {
        if (! VehicleUploadedImage::isValidStage($stage)) {
            abort(422, 'Invalid image stage.');
        }

        $vehicle->loadMissing('uploadedImages');

        $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $currentStages = VehicleGalleryMerger::resolveDisplayStages($raw, $vehicle);
        $currentUrls = $currentStages[$stage] ?? [];

        $orderedUrls = array_values(array_filter(
            $orderedUrls,
            fn ($url) => is_string($url) && $url !== '',
        ));

        if (! VehicleGalleryMerger::urlsMatchSameSet($orderedUrls, $currentUrls)) {
            abort(422, 'Image order must include the same gallery URLs for this stage.');
        }

        $stages = [];

        foreach (VehicleImageStages::STAGES as $stageKey) {
            $stages[$stageKey] = $stageKey === $stage
                ? $orderedUrls
                : [...($currentStages[$stageKey] ?? [])];
        }

        $raw['images_by_stage'] = $stages;
        $flatImages = VehicleGalleryMerger::flatten($stages, $vehicle, $raw);
        $raw['images'] = $flatImages;

        $vehicle->update([
            'images' => $flatImages,
            'raw_data' => $raw,
        ]);

        return $stages;
    }
}
