<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateManualVehicleAction;
use App\Actions\RestoreVehicleAction;
use App\Actions\UpdateManualVehicleAction;
use App\Enums\VehicleSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualVehicleRequest;
use App\Models\Vehicle;
use App\Services\VehicleUploadedImageService;
use App\Services\VpicDecoderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManualVehicleController extends Controller
{
    public function store(
        StoreManualVehicleRequest $request,
        CreateManualVehicleAction $action,
        VehicleUploadedImageService $gallery,
    ): JsonResponse {
        $vehicle = $action->execute($request->validated());

        return response()->json([
            'data' => $gallery->enrichListVehicle($vehicle->fresh(['activeAssignment.dealer'])),
            'message' => 'تمت إضافة السيارة يدوياً.',
        ], 201);
    }

    public function update(
        StoreManualVehicleRequest $request,
        Vehicle $vehicle,
        UpdateManualVehicleAction $action,
        VehicleUploadedImageService $gallery,
    ): JsonResponse {
        if ($vehicle->source !== VehicleSource::Manual) {
            return response()->json([
                'message' => 'لا يمكن تعديل سوى السيارات المُدخلة يدوياً.',
            ], 403);
        }

        try {
            $vehicle = $action->execute($vehicle, $request->validated());
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json([
            'data' => $gallery->enrichListVehicle($vehicle->fresh(['activeAssignment.dealer'])),
            'message' => 'تم تحديث السيارة.',
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        if ($vehicle->activeAssignment()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف السيارة لأنها مسندة لتاجر.',
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'message' => 'تم حذف السيارة.',
        ]);
    }

    public function restore(int $vehicle, RestoreVehicleAction $action): JsonResponse
    {
        $vehicle = Vehicle::withTrashed()->findOrFail($vehicle);

        if (! $vehicle->trashed()) {
            return response()->json([
                'message' => 'السيارة غير محذوفة.',
            ], 422);
        }

        $vehicle = $action->execute($vehicle);

        return response()->json([
            'data' => [
                'id' => $vehicle->id,
                'vin' => $vehicle->vin,
            ],
            'message' => 'تمت استعادة السيارة.',
        ]);
    }

    public function checkVin(string $vin, Request $request): JsonResponse
    {
        $vin = strtoupper(trim($vin));

        if (! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            return response()->json([
                'message' => 'رقم الشاصي غير صالح (17 حرفاً، بدون I أو O أو Q).',
            ], 422);
        }

        $excludeId = $request->filled('exclude') ? (int) $request->input('exclude') : null;

        $activeQuery = Vehicle::query()->where('vin', $vin);

        if ($excludeId) {
            $activeQuery->where('id', '!=', $excludeId);
        }

        $active = $activeQuery->first();

        if ($active !== null) {
            return response()->json([
                'data' => [
                    'exists' => true,
                    'trashed' => false,
                    'vehicle_id' => $active->id,
                ],
            ]);
        }

        $trashedQuery = Vehicle::onlyTrashed()->where('vin', $vin);

        if ($excludeId) {
            $trashedQuery->where('id', '!=', $excludeId);
        }

        $trashed = $trashedQuery->first();

        if ($trashed !== null) {
            return response()->json([
                'data' => [
                    'exists' => false,
                    'trashed' => true,
                    'vehicle_id' => $trashed->id,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'exists' => false,
                'trashed' => false,
                'vehicle_id' => null,
            ],
        ]);
    }

    public function decodeVin(string $vin, VpicDecoderService $decoder): JsonResponse
    {
        $vin = strtoupper(trim($vin));

        if (! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            return response()->json([
                'message' => 'رقم الشاصي غير صالح (17 حرفاً، بدون I أو O أو Q).',
            ], 422);
        }

        $active = Vehicle::query()->where('vin', $vin)->first();

        if ($active !== null) {
            return response()->json([
                'message' => 'رقم الشاصي مسجّل مسبقاً في النظام — لا يمكن إضافة نفس المركبة مرتين.',
                'data' => [
                    'exists' => true,
                    'trashed' => false,
                    'vehicle_id' => $active->id,
                ],
            ], 409);
        }

        $trashed = Vehicle::onlyTrashed()->where('vin', $vin)->first();

        try {
            $result = $decoder->decode($vin);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => [
                ...$result,
                'exists' => false,
                'trashed' => $trashed !== null,
                'vehicle_id' => $trashed?->id,
            ],
        ]);
    }
}
