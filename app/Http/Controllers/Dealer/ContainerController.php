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

        $data = $containers->listForDealer(
            $dealer,
            $request->string('container')->toString() ?: null,
            $request->string('chassis')->toString() ?: null,
        );

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

    public function vehicles(
        Request $request,
        string $container,
        ContainerService $containers,
    ): JsonResponse {
        $dealer = $request->user()->dealer;

        if (! $dealer) {
            abort(403, 'Dealer profile not found.');
        }

        $payload = $containers->vehiclesForContainer($container, $dealer);

        if ($payload === null) {
            return response()->json(['message' => 'Container not found.'], 404);
        }

        return response()->json(['data' => $payload]);
    }
}
