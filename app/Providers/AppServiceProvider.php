<?php

namespace App\Providers;

use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleUploadedImage;
use App\Observers\AdminVehicleIndexCacheObserver;
use Illuminate\Support\ServiceProvider;

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
        Vehicle::observe(AdminVehicleIndexCacheObserver::class);
        VehicleAssignment::observe(AdminVehicleIndexCacheObserver::class);
        VehicleUploadedImage::observe(AdminVehicleIndexCacheObserver::class);
    }
}
