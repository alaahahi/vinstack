<?php

use App\Http\Controllers\Admin\ContainerController as AdminContainerController;
use App\Http\Controllers\Admin\ContainerImageController as AdminContainerImageController;
use App\Http\Controllers\Admin\DatabaseBackupController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\ManualVehicleController;
use App\Http\Controllers\Admin\NujoomAlJazeeraImportController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleOptionsController;
use App\Http\Controllers\Admin\VehicleUploadedImageController;
use App\Http\Controllers\Admin\VinstackBrowseController;
use App\Http\Controllers\Admin\VinstackSettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VehicleImageDownloadController;
use App\Http\Controllers\VehicleGalleryController;
use App\Http\Controllers\Dealer\ContainerController as DealerContainerController;
use App\Http\Controllers\Dealer\ContainerImageController as DealerContainerImageController;
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
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword']);

        Route::get('/dealers', [DealerController::class, 'index']);
        Route::get('/dealers/summary', [DealerController::class, 'summary']);
        Route::post('/dealers', [DealerController::class, 'store']);
        Route::put('/dealers/{dealer}', [DealerController::class, 'update']);
        Route::delete('/dealers/{dealer}', [DealerController::class, 'destroy']);
        Route::get('/dealers/{dealer}/recovery-codes', [DealerController::class, 'recoveryCodes']);

        Route::get('/vehicles', [AdminVehicleController::class, 'index']);
        Route::get('/vehicles/check-vin/{vin}', [ManualVehicleController::class, 'checkVin']);
        Route::get('/vehicles/decode-vin/{vin}', [ManualVehicleController::class, 'decodeVin']);
        Route::post('/vehicles', [ManualVehicleController::class, 'store']);
        Route::put('/vehicles/{vehicle}', [ManualVehicleController::class, 'update']);
        Route::delete('/vehicles/{vehicle}', [ManualVehicleController::class, 'destroy']);
        Route::post('/vehicles/{vehicle}/restore', [ManualVehicleController::class, 'restore']);
        Route::post('/vehicles/import/nujoom/preview', [NujoomAlJazeeraImportController::class, 'preview']);
        Route::post('/vehicles/import/nujoom/apply', [NujoomAlJazeeraImportController::class, 'apply']);
        Route::get('/vehicles/{vehicle}/details', [AdminVehicleController::class, 'details']);
        Route::get('/vehicles/{vehicle}/gallery', [VehicleGalleryController::class, 'show']);
        Route::get('/vehicles/{vehicle}/images/download', [VehicleImageDownloadController::class, 'download']);
        Route::post('/vehicles/{vehicle}/images', [VehicleUploadedImageController::class, 'store']);
        Route::delete('/vehicles/{vehicle}/images/{image}', [VehicleUploadedImageController::class, 'destroy']);
        Route::post('/vehicles/{vehicle}/assign', [AdminVehicleController::class, 'assign']);
        Route::delete('/vehicles/{vehicle}/unassign', [AdminVehicleController::class, 'unassign']);

        Route::get('/settings/vehicle-options', [VehicleOptionsController::class, 'show']);
        Route::put('/settings/vehicle-options', [VehicleOptionsController::class, 'update']);

        Route::get('/system/migrations', [SystemController::class, 'migrations']);
        Route::post('/system/migrate', [SystemController::class, 'migrate']);
        Route::get('/system/logs', [SystemController::class, 'logs']);
        Route::delete('/system/logs', [SystemController::class, 'clearLogs']);

        Route::post('/system/backup', [DatabaseBackupController::class, 'backup']);
        Route::get('/system/backups', [DatabaseBackupController::class, 'index']);
        Route::get('/system/backups/{filename}/download', [DatabaseBackupController::class, 'download']);
        Route::delete('/system/backups/{filename}', [DatabaseBackupController::class, 'destroy']);
        Route::post('/system/restore', [DatabaseBackupController::class, 'restore']);

        Route::get('/vinstack/settings', [VinstackSettingsController::class, 'show']);
        Route::put('/vinstack/settings', [VinstackSettingsController::class, 'update']);
        Route::post('/vinstack/settings/gallery-test', [VinstackSettingsController::class, 'testGallery']);
        Route::post('/vinstack/settings/cloudinary-test', [VinstackSettingsController::class, 'testCloudinary']);
        Route::post('/vinstack/sync', [VinstackSettingsController::class, 'sync']);

        Route::get('/containers', [AdminContainerController::class, 'index']);
        Route::get('/containers/{container}/images', [AdminContainerImageController::class, 'index']);
        Route::post('/containers/{container}/images/upload', [AdminContainerImageController::class, 'upload']);
        Route::get('/containers/{container}/vehicles', [AdminContainerController::class, 'vehicles']);
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
        Route::get('/containers/{container}/images', [DealerContainerImageController::class, 'index']);
        Route::get('/containers/{container}/vehicles', [DealerContainerController::class, 'vehicles']);
        Route::get('/containers/{container}/tracking', [DealerContainerController::class, 'tracking']);
        Route::get('/vehicles', [DealerVehicleController::class, 'index']);
        Route::get('/vehicles/{vehicle}', [DealerVehicleController::class, 'show']);
        Route::get('/vehicles/{vehicle}/details', [DealerVehicleController::class, 'details']);
        Route::get('/vehicles/{vehicle}/gallery', [VehicleGalleryController::class, 'show']);
        Route::get('/vehicles/{vehicle}/images/download', [VehicleImageDownloadController::class, 'download']);
        Route::patch('/vehicles/{vehicle}/status', [DealerVehicleController::class, 'updateStatus']);
    });
});
