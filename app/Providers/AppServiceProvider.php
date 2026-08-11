<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleUploadedImage;
use App\Observers\AdminVehicleIndexCacheObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fail-soft / throttle / skip SQLite last_used_at writes (see PersonalAccessToken).
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Vehicle::observe(AdminVehicleIndexCacheObserver::class);
        VehicleAssignment::observe(AdminVehicleIndexCacheObserver::class);
        VehicleUploadedImage::observe(AdminVehicleIndexCacheObserver::class);
    }
}

