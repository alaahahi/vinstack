<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\UpdateVehicleStatusRequest;
use App\Models\Vehicle;
use App\Services\ContainerTrackingService;
use App\Services\VehicleDealerNoteNotificationService;
use App\Services\VehicleDetailService;
use App\Services\VehicleUploadedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(
        Request $request,
        VehicleUploadedImageService $gallery,
        ContainerTrackingService $tracking,
    ): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        $query = Vehicle::query()
            ->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            });

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $vehicles = $query
            ->with('uploadedImages')
            ->newestFirst()
            ->paginate(
                perPage: min((int) $request->input('per_page', 15), 100),
                page: (int) $request->input('page', 1),
            );

        $vehicles->through(fn (Vehicle $vehicle) => $gallery->enrichListVehicle($vehicle));

        return response()->json(array_merge(
            $vehicles->toArray(),
            ['tracking_available' => $tracking->trackingAvailable()],
        ));
    }

    public function show(Request $request, Vehicle $vehicle, VehicleUploadedImageService $gallery): JsonResponse
    {
        $this->ensureAssigned($request, $vehicle);

        $vehicle->load('uploadedImages');

        return response()->json(['data' => $gallery->enrichListVehicle($vehicle)]);
    }

    public function details(
        Request $request,
        Vehicle $vehicle,
        VehicleDetailService $details,
    ): JsonResponse {
        $this->ensureAssigned($request, $vehicle);

        return response()->json([
            'data' => $details->build($vehicle),
        ]);
    }

    public function updateStatus(
        UpdateVehicleStatusRequest $request,
        Vehicle $vehicle,
        VehicleDealerNoteNotificationService $noteNotifications,
    ): JsonResponse {
        $this->ensureAssigned($request, $vehicle);

        $validated = $request->validated();
        $previousNotes = $vehicle->notes;

        $vehicle->update([
            'status' => $validated['status'] ?? $vehicle->status,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $vehicle->notes,
        ]);

        $dealer = $request->user()->dealer;

        if ($dealer && array_key_exists('notes', $validated)) {
            $noteNotifications->recordIfChanged(
                $vehicle,
                $dealer,
                $request->user(),
                $previousNotes,
                $validated['notes'],
            );
        }

        return response()->json([
            'data' => $vehicle->fresh(),
            'message' => 'Notes saved.',
        ]);
    }

    protected function ensureAssigned(Request $request, Vehicle $vehicle): void
    {
        $dealerId = $request->user()->dealer?->id;

        $assigned = $vehicle->assignments()
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->exists();

        if (! $assigned) {
            abort(403, 'This vehicle is not assigned to you.');
        }
    }
}
