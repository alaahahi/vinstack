<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Support\VehicleEta;

class VehiclePurchaseDateNormalizer
{
    /**
     * Normalize raw_data.purchase_date values to Y-m-d for reliable list sorting.
     *
     * @return array{scanned: int, updated: int}
     */
    public function normalizeAll(int $chunkSize = 200): array
    {
        $scanned = 0;
        $updated = 0;

        Vehicle::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($vehicles) use (&$scanned, &$updated): void {
                foreach ($vehicles as $vehicle) {
                    $scanned++;

                    $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
                    $current = $raw['purchase_date'] ?? null;

                    if ($current === null || $current === '') {
                        continue;
                    }

                    $normalized = VehicleEta::normalize($current);

                    if ($normalized === null || $normalized === $current) {
                        continue;
                    }

                    $raw['purchase_date'] = $normalized;
                    $vehicle->update(['raw_data' => $raw]);
                    $updated++;
                }
            });

        if ($updated > 0) {
            AdminVehicleIndexCache::bumpVersion();
        }

        return [
            'scanned' => $scanned,
            'updated' => $updated,
        ];
    }
}
