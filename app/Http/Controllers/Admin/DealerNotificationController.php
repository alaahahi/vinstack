<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendDealerNotificationRequest;
use App\Http\Requests\Admin\UpdateWaQueueSettingsRequest;
use App\Models\Dealer;
use App\Models\VinstackSetting;
use App\Services\DealerNotificationService;
use App\Services\WaQueueService;
use App\Support\DealerNotificationEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerNotificationController extends Controller
{
    public function index(Request $request, DealerNotificationService $notifications): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max(1, (int) $request->input('per_page', 10)), 50);
        $result = $notifications->listPaginated($page, $perPage);

        return response()->json([
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function settings(WaQueueService $waQueue): JsonResponse
    {
        $settings = $waQueue->settings();
        $events = DealerNotificationEvents::normalize($settings->dealer_notification_events);

        return response()->json([
            'data' => [
                'wa_queue_base_url' => $settings->wa_queue_base_url ?? config('services.wa_queue.base_url'),
                'wa_queue_sender_id' => $settings->wa_queue_sender_id,
                'wa_queue_enabled' => (bool) $settings->wa_queue_enabled,
                'configured' => $waQueue->isConfigured(),
                'dealer_notification_events' => $events,
                'dealer_notification_event_catalog' => DealerNotificationEvents::catalog(),
            ],
        ]);
    }

    public function updateSettings(
        UpdateWaQueueSettingsRequest $request,
        WaQueueService $waQueue,
    ): JsonResponse {
        $settings = VinstackSetting::current();
        $data = $request->validated();

        if (array_key_exists('wa_queue_base_url', $data) && blank($data['wa_queue_base_url'])) {
            $data['wa_queue_base_url'] = null;
        }

        if (array_key_exists('dealer_notification_events', $data)) {
            $data['dealer_notification_events'] = DealerNotificationEvents::normalize($data['dealer_notification_events']);
        }

        $settings->update($data);
        $settings = $settings->fresh();

        return response()->json([
            'data' => [
                'wa_queue_base_url' => $settings->wa_queue_base_url,
                'wa_queue_sender_id' => $settings->wa_queue_sender_id,
                'wa_queue_enabled' => (bool) $settings->wa_queue_enabled,
                'configured' => $waQueue->isConfigured(),
                'dealer_notification_events' => DealerNotificationEvents::normalize($settings->dealer_notification_events),
                'dealer_notification_event_catalog' => DealerNotificationEvents::catalog(),
            ],
            'message' => 'تم حفظ إعدادات WA Queue.',
        ]);
    }

    public function testConnection(WaQueueService $waQueue): JsonResponse
    {
        $result = $waQueue->probeConnection();

        return response()->json([
            'data' => $result,
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }

    public function send(
        SendDealerNotificationRequest $request,
        DealerNotificationService $notifications,
    ): JsonResponse {
        $message = $request->string('message')->toString();

        if ($request->boolean('send_to_all')) {
            $result = $notifications->sendManualToAllDealers(
                $message,
                $request->user(),
            );

            return response()->json([
                'data' => $result['logs'] ?? [],
                'message' => $result['message'],
                'sent' => $result['sent'] ?? 0,
                'failed' => $result['failed'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ], $result['ok'] ? 201 : 422);
        }

        $dealer = Dealer::query()->findOrFail($request->integer('dealer_id'));

        $result = $notifications->sendManualToDealer(
            $dealer,
            $message,
            $request->user(),
        );

        return response()->json([
            'data' => $result['log'] ?? null,
            'message' => $result['message'],
            'wa_queue_status' => $result['status'] ?? null,
            'errors' => $result['errors'] ?? null,
        ], $result['ok'] ? 201 : 422);
    }

    public function dealers(): JsonResponse
    {
        $dealers = Dealer::query()
            ->orderByDesc('id')
            ->get(['id', 'company_name', 'phone'])
            ->map(fn (Dealer $dealer) => [
                'id' => $dealer->id,
                'company_name' => $dealer->company_name,
                'phone' => $dealer->phone,
                'has_phone' => filled($dealer->phone),
            ]);

        return response()->json(['data' => $dealers]);
    }
}
