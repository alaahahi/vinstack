<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\VehicleMessageService;
use App\Services\VehicleStatusNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(
        Request $request,
        VehicleMessageService $messages,
        VehicleStatusNotificationService $statusNotifications,
    ): JsonResponse {
        $limit = min((int) $request->input('limit', 30), 100);

        $messageThreads = $messages->listUnreadThreadsForAdmin($limit)
            ->map(fn (array $item) => [
                'type' => 'dealer_message',
                ...$item,
            ]);

        $statusItems = $statusNotifications->listUnreadRecent($limit);

        $items = $messageThreads
            ->concat($statusItems)
            ->sortByDesc(fn (array $item) => $item['created_at'] ?? '')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $items,
            'unread_count' => $this->unreadTotal($messages, $statusNotifications),
        ]);
    }

    public function unreadCount(
        VehicleMessageService $messages,
        VehicleStatusNotificationService $statusNotifications,
    ): JsonResponse {
        return response()->json([
            'unread_count' => $this->unreadTotal($messages, $statusNotifications),
        ]);
    }

    public function markStatusRead(
        int $notification,
        VehicleStatusNotificationService $statusNotifications,
    ): JsonResponse {
        $row = $statusNotifications->markRead($notification);

        if (! $row) {
            return response()->json(['message' => 'الإشعار غير موجود.'], 404);
        }

        return response()->json(['data' => $row]);
    }

    protected function unreadTotal(
        VehicleMessageService $messages,
        VehicleStatusNotificationService $statusNotifications,
    ): int {
        return $messages->unreadCountForViewer(UserRole::Admin)
            + $statusNotifications->unreadCount();
    }
}
