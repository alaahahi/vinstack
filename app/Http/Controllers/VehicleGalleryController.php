<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\VinstackGalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleGalleryController extends Controller
{
    public function show(
        Request $request,
        Vehicle $vehicle,
        VinstackGalleryService $gallery,
    ): JsonResponse {
        $this->authorizeVehicleAccess($request, $vehicle);

        return response()->json([
            'data' => $gallery->buildGalleryPayload($vehicle),
        ]);
    }

    protected function authorizeVehicleAccess(Request $request, Vehicle $vehicle): void
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return;
        }

        $dealerId = $user?->dealer?->id;

        if (! $dealerId) {
            abort(403, 'Dealer profile not found.');
        }

        $assigned = $vehicle->assignments()
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->exists();

        if (! $assigned) {
            abort(403, 'This vehicle is not assigned to you.');
        }
    }
}
