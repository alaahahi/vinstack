<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleMessageRequest;
use App\Models\Vehicle;
use App\Services\VehicleMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleMessageController extends Controller
{
    public function index(
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        return response()->json([
            'data' => $messages->listForVehicle($vehicle, UserRole::Admin),
        ]);
    }

    public function store(
        StoreVehicleMessageRequest $request,
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        $message = $messages->send(
            $vehicle,
            $request->user(),
            UserRole::Admin,
            $request->input('body'),
            $request->file('image'),
        );

        return response()->json([
            'data' => $message,
            'message' => 'Message sent.',
        ], 201);
    }

    public function markRead(
        Vehicle $vehicle,
        VehicleMessageService $messages,
    ): JsonResponse {
        $updated = $messages->markReadForViewer($vehicle, UserRole::Admin);

        return response()->json([
            'updated' => $updated,
            'unread_count' => $messages->unreadCountForViewer(UserRole::Admin),
        ]);
    }

    public function unreadCount(VehicleMessageService $messages): JsonResponse
    {
        return response()->json([
            'unread_count' => $messages->unreadCountForViewer(UserRole::Admin),
        ]);
    }
}
