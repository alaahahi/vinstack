<?php

use App\Http\Controllers\Admin\ContainerController as AdminContainerController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\VinstackBrowseController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleUploadedImageController;
use App\Http\Controllers\Admin\VinstackSettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VehicleImageDownloadController;
use App\Http\Controllers\Dealer\ContainerController as DealerContainerController;
use App\Http\Controllers\Dealer\HeartbeatController as DealerHeartbeatController;
use App\Http\Controllers\Dealer\ProfileController as DealerProfileController;
use App\Http\Controllers\Dealer\TwoFactorController as DealerTwoFactorController;
use App\Http\Controllers\Dealer\VehicleController as DealerVehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/settings/public', [PublicSettingsController::class, 'show']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/two-factor/setup', [TwoFactorController::class, 'setup']);
Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm']);
Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/profile', [AdminProfileController::class, 'show']);
        Route::put('/profile', [AdminProfileController::class, 'update']);

        Route::get('/dealers', [DealerController::class, 'index']);
        Route::post('/dealers', [DealerController::class, 'store']);
        Route::put('/dealers/{dealer}', [DealerController::class, 'update']);
        Route::get('/dealers/{dealer}/recovery-codes', [DealerController::class, 'recoveryCodes']);

        Route::get('/vehicles', [AdminVehicleController::class, 'index']);
        Route::get('/vehicles/{vehicle}/details', [AdminVehicleController::class, 'details']);
        Route::get('/vehicles/{vehicle}/images/download', [VehicleImageDownloadController::class, 'download']);
        Route::post('/vehicles/{vehicle}/images', [VehicleUploadedImageController::class, 'store']);
        Route::delete('/vehicles/{vehicle}/images/{image}', [VehicleUploadedImageController::class, 'destroy']);
        Route::post('/vehicles/{vehicle}/assign', [AdminVehicleController::class, 'assign']);

        Route::get('/vinstack/settings', [VinstackSettingsController::class, 'show']);
        Route::put('/vinstack/settings', [VinstackSettingsController::class, 'update']);
        Route::post('/vinstack/sync', [VinstackSettingsController::class, 'sync']);

        Route::get('/containers', [AdminContainerController::class, 'index']);
        Route::get('/containers/{container}/tracking', [AdminContainerController::class, 'tracking']);
        Route::get('/vinstack/containers', [VinstackBrowseController::class, 'containers']);
        Route::get('/vinstack/invoices', [VinstackBrowseController::class, 'invoices']);
        Route::get('/vinstack/loading-lists', [VinstackBrowseController::class, 'loadingLists']);
        Route::get('/vinstack/payments', [VinstackBrowseController::class, 'payments']);
        Route::get('/vinstack/parts', [VinstackBrowseController::class, 'parts']);
        Route::get('/vinstack/quotes', [VinstackBrowseController::class, 'quotes']);
    });

    Route::middleware('role:dealer')->prefix('dealer')->group(function () {
        Route::post('/heartbeat', [DealerHeartbeatController::class, 'store']);
        Route::get('/profile', [DealerProfileController::class, 'show']);
        Route::put('/profile', [DealerProfileController::class, 'update']);
        Route::post('/two-factor/recovery-codes', [DealerTwoFactorController::class, 'regenerateRecoveryCodes']);
        Route::get('/stats', [DealerProfileController::class, 'stats']);
        Route::get('/containers', [DealerContainerController::class, 'index']);
        Route::get('/containers/{container}/tracking', [DealerContainerController::class, 'tracking']);
        Route::get('/vehicles', [DealerVehicleController::class, 'index']);
        Route::get('/vehicles/{vehicle}', [DealerVehicleController::class, 'show']);
        Route::get('/vehicles/{vehicle}/details', [DealerVehicleController::class, 'details']);
        Route::get('/vehicles/{vehicle}/images/download', [VehicleImageDownloadController::class, 'download']);
        Route::patch('/vehicles/{vehicle}/status', [DealerVehicleController::class, 'updateStatus']);
    });
});
