<?php

namespace App\Http\Controllers\Dealer;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreVehicleMessageRequest;
use App\Models\Vehicle;
use App\Services\VehicleMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleMessageController extends Controller
{
    public function index(
        Request $request,
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        $this->ensureAssigned($request, $vehicle);

        return response()->json([
            'data' => $messages->listForVehicle($vehicle, UserRole::Dealer),
        ]);
    }

    public function store(
        StoreVehicleMessageRequest $request,
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        $this->ensureAssigned($request, $vehicle);

        $message = $messages->send(
            $vehicle,
            $request->user(),
            UserRole::Dealer,
            $request->input('body'),
            $request->file('image'),
        );

        return response()->json([
            'data' => $message,
            'message' => 'Message sent.',
        ], 201);
    }

    public function markRead(
        Request $request,
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        $this->ensureAssigned($request, $vehicle);

        $updated = $messages->markReadForViewer($vehicle, UserRole::Dealer);

        return response()->json([
            'updated' => $updated,
            'unread_count' => $messages->unreadCountForViewer(
                UserRole::Dealer,
                $request->user()->dealer?->id,
            ),
        ]);
    }

    public function unreadCount(Request $request, VehicleMessageService $messages): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        return response()->json([
            'unread_count' => $messages->unreadCountForViewer(UserRole::Dealer, $dealer->id),
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
