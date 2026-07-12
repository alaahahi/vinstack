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

        $container = $request->string('container')->trim()->toString() ?: null;
        $chassis = $request->string('chassis')->trim()->toString() ?: null;
        $perPage = min((int) $request->input('per_page', 50), 100);
        $page = max(1, (int) $request->input('page', 1));

        $all = $containers->listForDealer(
            $dealer,
            $container,
            $chassis,
        );

        $total = count($all);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($all, $offset, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'data' => $slice,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? min($offset + count($slice), $total) : null,
                'has_more' => $page < $lastPage,
            ],
            'total' => $total,
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
