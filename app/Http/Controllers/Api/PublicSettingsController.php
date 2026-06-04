<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VinstackSetting;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = VinstackSetting::current();

        return response()->json([
            'data' => [
                'support_phone' => $settings->support_phone ?? '',
            ],
        ]);
    }
}
