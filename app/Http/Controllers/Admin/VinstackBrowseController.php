<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContainerService;
use App\Services\VinstackService;
use Illuminate\Http\JsonResponse;

class VinstackBrowseController extends Controller
{
    public function containers(ContainerService $containers): JsonResponse
    {
        $data = $containers->listForAdmin();

        return response()->json([
            'data' => $data,
            'total' => count($data),
            'tracking_available' => $containers->trackingAvailable(),
        ]);
    }

    public function invoices(VinstackService $vinstack): JsonResponse
    {
        return $this->respond($vinstack->invoices());
    }

    public function loadingLists(VinstackService $vinstack): JsonResponse
    {
        return $this->respond($vinstack->loadingLists());
    }

    public function payments(VinstackService $vinstack): JsonResponse
    {
        return $this->respond($vinstack->payments());
    }

    public function parts(VinstackService $vinstack): JsonResponse
    {
        return $this->respond($vinstack->parts());
    }

    public function quotes(VinstackService $vinstack): JsonResponse
    {
        return $this->respond($vinstack->quotes());
    }

    protected function respond(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'total' => count($data),
        ]);
    }
}
