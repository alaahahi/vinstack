<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SyncVehiclesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVinstackSettingsRequest;
use App\Models\VinstackSetting;
use Illuminate\Http\JsonResponse;

class VinstackSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = VinstackSetting::current();

        return response()->json([
            'data' => [
                'api_base_url' => $settings->api_base_url,
                'has_token' => (bool) $settings->api_token,
                'sync_enabled' => $settings->sync_enabled,
                'last_sync_at' => $settings->last_sync_at,
                'support_phone' => $settings->support_phone ?? '',
            ],
        ]);
    }

    public function update(UpdateVinstackSettingsRequest $request): JsonResponse
    {
        $settings = VinstackSetting::current();
        $data = $request->validated();

        if (array_key_exists('api_token', $data) && blank($data['api_token'])) {
            unset($data['api_token']);
        }

        if (array_key_exists('support_phone', $data) && blank($data['support_phone'])) {
            $data['support_phone'] = null;
        }

        $settings->update($data);

        return response()->json([
            'data' => [
                'api_base_url' => $settings->api_base_url,
                'has_token' => (bool) $settings->api_token,
                'sync_enabled' => $settings->sync_enabled,
                'last_sync_at' => $settings->last_sync_at,
                'support_phone' => $settings->support_phone ?? '',
            ],
            'message' => 'Settings saved.',
        ]);
    }

    public function sync(SyncVehiclesAction $action): JsonResponse
    {
        try {
            $result = $action->execute();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Sync completed.',
            'created' => $result['created'],
            'updated' => $result['updated'],
            'total' => $result['total'],
        ]);
    }
}
