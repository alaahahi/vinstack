<?php

namespace App\Observers;

use App\Services\AdminVehicleIndexCache;

class AdminVehicleIndexCacheObserver
{
    public function saved(object $model): void
    {
        AdminVehicleIndexCache::bumpVersion();
    }

    public function deleted(object $model): void
    {
        AdminVehicleIndexCache::bumpVersion();
    }

    public function restored(object $model): void
    {
        AdminVehicleIndexCache::bumpVersion();
    }

    public function forceDeleted(object $model): void
    {
        AdminVehicleIndexCache::bumpVersion();
    }
}
