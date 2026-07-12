<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleUploadedImagesRequest;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Services\CloudinaryService;
use App\Services\DealerNotificationService;
use App\Services\ImageTransferService;
use App\Services\VehicleDetailService;
use App\Services\VehicleUploadedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VehicleUploadedImageController extends Controller
{
    public function store(
        StoreVehicleUploadedImagesRequest $request,
        Vehicle $vehicle,
        VehicleUploadedImageService $uploads,
        ImageTransferService $transfers,
        CloudinaryService $cloudinary,
        VehicleDetailService $details,
        DealerNotificationService $notifications,
    ): JsonResponse {
        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = $request->file('images', []);
        $stage = $request->string('stage')->toString();
        $user = $request->user();

        if ($transfers->asyncEnabled() && $cloudinary->isConfigured() && $user) {
            try {
                $job = $transfers->createVehicleImagesJob($vehicle, $stage, $files, $user);
            } catch (RuntimeException $e) {
                return response()->json([
                    'message' => $e->getMessage() === 'Cloudinary is not configured.'
                        ? 'Cloudinary is not configured.'
                        : $e->getMessage(),
                ], 422);
            } catch (\Throwable $e) {
                Log::error('Vehicle images staging failed', [
                    'vehicle_id' => $vehicle->id,
                    'stage' => $stage,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'تعذّر استلام الصور على الخادم.',
                ], 422);
            }

            return response()->json([
                'data' => [
                    'transfer' => $job->fresh()->toApiArray(),
                    'async' => true,
                ],
                'message' => 'تم الرفع — معالجة الصور جارية في الخلفية.',
            ], 202);
        }

        $created = $uploads->storeMany(
            $vehicle,
            $stage,
            $files,
            $user,
        );

        if (count($created) > 0) {
            $notifications->notifyVehicleImagesAdded(
                $vehicle,
                count($created),
                $stage,
                $user,
            );
        }

        return response()->json([
            'data' => [
                'uploaded' => $created,
                'vehicle' => $details->build($vehicle, includeAssignment: true),
            ],
            'message' => count($created) === 1
                ? 'Image uploaded successfully.'
                : count($created).' images uploaded successfully.',
        ], 201);
    }

    public function destroy(
        Vehicle $vehicle,
        VehicleUploadedImage $image,
        VehicleUploadedImageService $uploads,
        VehicleDetailService $details,
    ): JsonResponse {
        if ($image->vehicle_id !== $vehicle->id) {
            abort(404);
        }

        $result = $uploads->delete($vehicle, $image);

        return response()->json([
            'data' => $details->build($vehicle, includeAssignment: true),
            'message' => $result['cloudinary_warning'] ?? 'Image removed successfully.',
            'cloudinary_warning' => $result['cloudinary_warning'],
        ]);
    }
}
