<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Throwable;

class SystemCacheService
{
    public function __construct(
        protected VehiclePurchaseDateNormalizer $purchaseDates,
    ) {}

    /**
     * Clear application caches and invalidate versioned index caches.
     *
     * @return array{
     *     cleared: list<string>,
     *     vehicle_index_version_bumped: bool,
     *     purchase_dates_normalized: int
     * }
     */
    public function clear(): array
    {
        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
        ];

        $cleared = [];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $cleared[] = $command;
            } catch (Throwable) {
                // Continue clearing remaining stores even if one command fails.
            }
        }

        $normalized = 0;

        try {
            $normalized = $this->purchaseDates->normalizeAll()['updated'];
        } catch (Throwable) {
            // Cache clear should still succeed if date backfill fails.
        }

        AdminVehicleIndexCache::bumpVersion();

        return [
            'cleared' => $cleared,
            'vehicle_index_version_bumped' => true,
            'purchase_dates_normalized' => $normalized,
        ];
    }
}
