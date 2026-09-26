<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAccountingExportSettingsRequest;
use App\Models\AccountingExportSetting;
use App\Models\Vehicle;
use App\Services\CopartExportService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AccountingExportController extends Controller
{
    public function settings(): JsonResponse
    {
        $settings = AccountingExportSetting::current();

        return response()->json([
            'data' => [
                'api_base_url' => $settings->api_base_url,
                'enabled' => $settings->enabled,
                'has_token' => filled($settings->api_token) || filled(config('services.copart.token')),
            ],
        ]);
    }

    public function updateSettings(UpdateAccountingExportSettingsRequest $request): JsonResponse
    {
        $settings = AccountingExportSetting::current();
        $data = $request->validated();

        if (array_key_exists('api_token', $data) && trim((string) $data['api_token']) === '') {
            unset($data['api_token']);
        }

        $settings->fill($data);
        $settings->save();

        return response()->json([
            'data' => [
                'api_base_url' => $settings->api_base_url,
                'enabled' => $settings->enabled,
                'has_token' => filled($settings->api_token) || filled(config('services.copart.token')),
            ],
            'message' => 'تم حفظ إعدادات تصدير المحاسبة.',
        ]);
    }

    public function exportVehicle(Vehicle $vehicle, CopartExportService $export): JsonResponse
    {
        try {
            $result = $export->export($vehicle->fresh());
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => [
                    'accounting_export_status' => $vehicle->fresh()?->accounting_export_status,
                    'accounting_export_error' => $vehicle->fresh()?->accounting_export_error,
                ],
            ], 422);
        }

        $vehicle->refresh();

        $message = 'تم تحديث السيارة في نظام الحسابات.';
        if (! empty($result['queued'])) {
            $message = 'تم الإرسال لقائمة موافقة المحاسبة في Copart.';
        } elseif (! empty($result['created'])) {
            $message = 'تم تصدير السيارة إلى نظام الحسابات.';
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'copart_car_id' => $vehicle->copart_car_id,
                'exported_to_accounting_at' => $vehicle->exported_to_accounting_at,
                'accounting_export_status' => $vehicle->accounting_export_status,
                'result' => $result,
            ],
        ]);
    }
}
