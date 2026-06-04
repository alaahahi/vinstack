<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContainerService;
use App\Services\ContainerTrackingService;
use Illuminate\Http\JsonResponse;

class ContainerController extends Controller
{
    public function index(ContainerService $containers): JsonResponse
    {
        $data = $containers->listForAdmin();

        return response()->json([
            'data' => $data,
            'total' => count($data),
            'tracking_available' => $containers->trackingAvailable(),
        ]);
    }

    public function tracking(string $container, ContainerTrackingService $tracking): JsonResponse
    {
        return response()->json($tracking->forAdmin($container));
    }
}
