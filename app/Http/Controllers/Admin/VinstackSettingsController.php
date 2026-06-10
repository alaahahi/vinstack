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
            'data' => $this->settingsPayload($settings),
        ]);
    }

    public function update(UpdateVinstackSettingsRequest $request): JsonResponse
    {
        $settings = VinstackSetting::current();
        $data = $request->validated();

        if (array_key_exists('api_token', $data) && blank($data['api_token'])) {
            unset($data['api_token']);
        }

        if (array_key_exists('gallery_api_token', $data) && blank($data['gallery_api_token'])) {
            unset($data['gallery_api_token']);
        }

        if (array_key_exists('support_phone', $data) && blank($data['support_phone'])) {
            $data['support_phone'] = null;
        }

        if (array_key_exists('gallery_api_token', $data)) {
            $data['gallery_token_expired'] = false;
        }

        $settings->update($data);

        return response()->json([
            'data' => $this->settingsPayload($settings->fresh()),
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
            'restorable' => $result['restorable'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingsPayload(VinstackSetting $settings): array
    {
        return [
            'api_base_url' => $settings->api_base_url,
            'has_token' => (bool) $settings->api_token,
            'gallery_api_base_url' => $settings->gallery_api_base_url,
            'has_gallery_token' => (bool) $settings->gallery_api_token,
            'gallery_token_expired' => (bool) $settings->gallery_token_expired,
            'gallery_token_checked_at' => $settings->gallery_token_checked_at,
            'sync_enabled' => $settings->sync_enabled,
            'last_sync_at' => $settings->last_sync_at,
            'last_auto_sync_at' => $settings->last_auto_sync_at,
            'support_phone' => $settings->support_phone ?? '',
        ];
    }
}
