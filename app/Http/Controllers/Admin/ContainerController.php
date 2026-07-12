<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Services\ContainerService;

use App\Services\ContainerTrackingService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class ContainerController extends Controller

{

    public function index(Request $request, ContainerService $containers): JsonResponse

    {

        $dealerId = $request->filled('dealer_id') ? (int) $request->input('dealer_id') : null;

        $container = $request->string('container')->trim()->toString() ?: null;

        $chassis = $request->string('chassis')->trim()->toString()

            ?: $request->string('vin')->trim()->toString()

            ?: null;

        $perPage = min((int) $request->input('per_page', 50), 100);

        $page = max(1, (int) $request->input('page', 1));



        $all = $containers->listForAdminFiltered(
            $dealerId,
            $chassis !== '' ? $chassis : null,
            $container !== '' ? $container : null,
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



    public function tracking(string $container, ContainerTrackingService $tracking): JsonResponse

    {

        return response()->json($tracking->forAdmin($container));

    }

    public function vehicles(string $container, ContainerService $containers): JsonResponse
    {
        $payload = $containers->vehiclesForContainer($container);

        if ($payload === null) {
            return response()->json(['message' => 'Container not found.'], 404);
        }

        return response()->json(['data' => $payload]);
    }

}

