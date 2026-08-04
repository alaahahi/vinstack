<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Throwable;

class SystemCacheService
{
    /**
     * Clear application caches and invalidate versioned index caches.
     *
     * @return array{cleared: list<string>, vehicle_index_version_bumped: bool}
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

        AdminVehicleIndexCache::bumpVersion();

        return [
            'cleared' => $cleared,
            'vehicle_index_version_bumped' => true,
        ];
    }
}
