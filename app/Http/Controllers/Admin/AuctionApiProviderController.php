<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAuctionApiProviderRequest;
use App\Http\Requests\Admin\UpdateAuctionApiProviderRequest;
use App\Models\AuctionApiProvider;
use App\Services\AuctionApiProviderService;
use Illuminate\Http\JsonResponse;

class AuctionApiProviderController extends Controller
{
    public function __construct(
        protected AuctionApiProviderService $providers,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'providers' => $this->providers->listSummaries(),
                'active' => $this->providers->activeSummary(),
            ],
        ]);
    }

    public function store(StoreAuctionApiProviderRequest $request): JsonResponse
    {
        $provider = $this->providers->store($request->validated());

        return response()->json([
            'ok' => true,
            'data' => $this->providers->present($provider),
            'message' => 'تم إضافة مفتاح مزاد API.',
        ], 201);
    }

    public function update(UpdateAuctionApiProviderRequest $request, AuctionApiProvider $provider): JsonResponse
    {
        $provider = $this->providers->update($provider, $request->validated());

        return response()->json([
            'ok' => true,
            'data' => $this->providers->present($provider),
            'message' => 'تم حفظ مفتاح مزاد API.',
        ]);
    }

    public function destroy(AuctionApiProvider $provider): JsonResponse
    {
        $this->providers->delete($provider);

        return response()->json([
            'ok' => true,
            'message' => 'تم حذف مفتاح مزاد API.',
        ]);
    }

    public function activate(AuctionApiProvider $provider): JsonResponse
    {
        $provider = $this->providers->activate($provider, 'manual');

        return response()->json([
            'ok' => true,
            'data' => $this->providers->present($provider),
            'message' => 'تم تفعيل مفتاح مزاد API.',
        ]);
    }
}
