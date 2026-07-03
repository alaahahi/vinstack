<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Services\ContainerImageService;
use App\Services\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContainerImageController extends Controller
{
    public function index(
        Request $request,
        string $container,
        ContainerImageService $images,
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

        $lookupKeys = $containers->imageLookupKeysForContainer($container, $payload['container']);

        return response()->json([
            'data' => $images->payloadForContainerKeys($lookupKeys),
        ]);
    }
}
