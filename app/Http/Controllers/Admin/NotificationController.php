<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VehicleDealerNoteNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, VehicleDealerNoteNotificationService $notifications): JsonResponse
    {
        $limit = min((int) $request->input('limit', 30), 100);

        return response()->json([
            'data' => $notifications->listRecent($limit),
            'unread_count' => $notifications->unreadCount(),
        ]);
    }

    public function unreadCount(VehicleDealerNoteNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'unread_count' => $notifications->unreadCount(),
        ]);
    }

    public function show(int $notification, VehicleDealerNoteNotificationService $notifications): JsonResponse
    {
        $payload = $notifications->findForAdmin($notification);

        if ($payload === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json(['data' => $payload]);
    }

    public function markRead(int $notification, VehicleDealerNoteNotificationService $notifications): JsonResponse
    {
        $payload = $notifications->markRead($notification);

        if ($payload === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'data' => $payload,
            'unread_count' => $notifications->unreadCount(),
        ]);
    }
}
