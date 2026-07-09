<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendDealerNotificationRequest;
use App\Http\Requests\Admin\UpdateWaQueueSettingsRequest;
use App\Models\Dealer;
use App\Models\VinstackSetting;
use App\Services\DealerNotificationService;
use App\Services\WaQueueService;
use Illuminate\Http\JsonResponse;

class DealerNotificationController extends Controller
{
    public function index(DealerNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'data' => $notifications->listRecent(60),
        ]);
    }

    public function settings(WaQueueService $waQueue): JsonResponse
    {
        $settings = $waQueue->settings();

        return response()->json([
            'data' => [
                'wa_queue_base_url' => $settings->wa_queue_base_url ?? config('services.wa_queue.base_url'),
                'wa_queue_sender_id' => $settings->wa_queue_sender_id,
                'wa_queue_enabled' => (bool) $settings->wa_queue_enabled,
                'configured' => $waQueue->isConfigured(),
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

        $settings->update($data);

        return response()->json([
            'data' => [
                'wa_queue_base_url' => $settings->fresh()->wa_queue_base_url,
                'wa_queue_sender_id' => $settings->wa_queue_sender_id,
                'wa_queue_enabled' => (bool) $settings->wa_queue_enabled,
                'configured' => $waQueue->isConfigured(),
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
        $dealer = Dealer::query()->findOrFail($request->integer('dealer_id'));

        $result = $notifications->sendManualToDealer(
            $dealer,
            $request->string('message')->toString(),
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
