<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Services\ContainerService;
use App\Services\ContainerTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContainerController extends Controller
{
    public function index(Request $request, ContainerService $containers): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        $data = $containers->listForDealer($dealer);

        return response()->json([
            'data' => $data,
            'total' => count($data),
            'tracking_available' => $containers->trackingAvailable(),
        ]);
    }

    public function tracking(
        Request $request,
        string $container,
        ContainerTrackingService $tracking,
    ): JsonResponse {
        $dealer = $request->user()->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        return response()->json($tracking->forDealer($dealer, $container));
    }
}
