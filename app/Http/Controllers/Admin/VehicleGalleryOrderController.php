<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderVehicleGalleryRequest;
use App\Models\Vehicle;
use App\Services\VehicleDetailService;
use App\Services\VehicleGalleryOrderService;
use Illuminate\Http\JsonResponse;

class VehicleGalleryOrderController extends Controller
{
    public function update(
        ReorderVehicleGalleryRequest $request,
        Vehicle $vehicle,
        VehicleGalleryOrderService $order,
        VehicleDetailService $details,
    ): JsonResponse {
        $order->reorderStage(
            $vehicle,
            $request->string('stage')->toString(),
            $request->input('urls', []),
        );

        return response()->json([
            'data' => $details->build($vehicle->fresh(), includeAssignment: true),
            'message' => 'Gallery order updated.',
        ]);
    }
}
