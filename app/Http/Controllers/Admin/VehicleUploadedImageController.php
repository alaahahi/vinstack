<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleUploadedImagesRequest;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Services\VehicleDetailService;
use App\Services\VehicleUploadedImageService;
use Illuminate\Http\JsonResponse;

class VehicleUploadedImageController extends Controller
{
    public function store(
        StoreVehicleUploadedImagesRequest $request,
        Vehicle $vehicle,
        VehicleUploadedImageService $uploads,
        VehicleDetailService $details,
    ): JsonResponse {
        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = $request->file('images', []);

        $created = $uploads->storeMany(
            $vehicle,
            $request->string('stage')->toString(),
            $files,
            $request->user(),
        );

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
