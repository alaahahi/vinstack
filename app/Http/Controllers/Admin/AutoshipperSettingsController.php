<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SyncAutoShipperVehiclesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAutoshipperSettingsRequest;
use App\Models\AutoshipperSetting;
use Illuminate\Http\JsonResponse;

class AutoshipperSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = AutoshipperSetting::current();

        return response()->json([
            'data' => $this->settingsPayload($settings),
        ]);
    }

    public function update(UpdateAutoshipperSettingsRequest $request): JsonResponse
    {
        $settings = AutoshipperSetting::current();
        $data = $request->validated();

        if (array_key_exists('api_token', $data) && blank($data['api_token'])) {
            unset($data['api_token']);
        }

        if (array_key_exists('api_base_url', $data) && blank($data['api_base_url'])) {
            $data['api_base_url'] = config('services.autoshipper.base_url');
        }

        $settings->update($data);

        return response()->json([
            'data' => $this->settingsPayload($settings->fresh()),
            'message' => 'AutoShipper settings saved.',
        ]);
    }

    public function sync(SyncAutoShipperVehiclesAction $action): JsonResponse
    {
        try {
            $result = $action->execute();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        AutoshipperSetting::current()->update([
            'last_sync_at' => now(),
        ]);

        return response()->json([
            'message' => 'AutoShipper sync completed.',
            'created' => $result['created'],
            'updated' => $result['updated'],
            'total' => $result['total'],
            'skipped' => $result['skipped'],
            'restorable' => $result['restorable'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingsPayload(AutoshipperSetting $settings): array
    {
        return [
            'api_base_url' => $settings->api_base_url
                ?: config('services.autoshipper.base_url')
                ?: 'https://autoshipper.io/api',
            'has_token' => filled($settings->api_token),
            'sync_enabled' => (bool) ($settings->sync_enabled ?? true),
            'last_sync_at' => $settings->last_sync_at,
            'last_auto_sync_at' => $settings->last_auto_sync_at,
        ];
    }
}
