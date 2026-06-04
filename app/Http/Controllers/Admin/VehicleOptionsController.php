<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVehicleOptionsRequest;
use App\Models\VinstackSetting;
use App\Support\VehicleOptions;
use Illuminate\Http\JsonResponse;

class VehicleOptionsController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = VinstackSetting::current();

        return response()->json([
            'data' => VehicleOptions::resolve($settings->vehicle_options),
        ]);
    }

    public function update(UpdateVehicleOptionsRequest $request): JsonResponse
    {
        $settings = VinstackSetting::current();
        $current = VehicleOptions::resolve($settings->vehicle_options);
        $incoming = [];

        foreach (VehicleOptions::KEYS as $key) {
            if ($request->has($key)) {
                $incoming[$key] = VehicleOptions::normalizeList($request->input($key));
            } else {
                $incoming[$key] = $current[$key];
            }
        }

        $settings->update(['vehicle_options' => $incoming]);

        return response()->json([
            'data' => $incoming,
            'message' => 'تم حفظ خيارات النموذج.',
        ]);
    }
}
