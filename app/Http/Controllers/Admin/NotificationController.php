<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\VehicleMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, VehicleMessageService $messages): JsonResponse
    {
        $limit = min((int) $request->input('limit', 30), 100);

        return response()->json([
            'data' => $messages->listUnreadThreadsForAdmin($limit),
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
