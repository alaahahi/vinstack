<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAuctionSpotlightRequest;
use App\Services\AuctionSpotlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionSpotlightController extends Controller
{
    public function __construct(
        protected AuctionSpotlightService $spotlight,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $enabled = $this->spotlight->enabled();
        $items = $enabled ? $this->spotlight->list() : [];

        return response()->json([
            'ok' => true,
            'data' => $items,
            'meta' => [
                'enabled' => $enabled,
                'count' => count($items),
            ],
        ]);
    }

    public function store(StoreAuctionSpotlightRequest $request): JsonResponse
    {
        $item = $this->spotlight->record($request->user(), $request->validated());

        return response()->json([
            'ok' => true,
            'data' => $item,
            'meta' => [
                'enabled' => $this->spotlight->enabled(),
            ],
        ], $item ? 201 : 200);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        if ($request->user()?->role !== UserRole::Admin) {
            abort(403, 'Unauthorized for this area.');
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = $this->spotlight->setEnabled((bool) $validated['enabled']);

        return response()->json([
            'ok' => true,
            'message' => $enabled ? 'تم تفعيل سلايدر السيارات المفتوحة.' : 'تم إيقاف سلايدر السيارات المفتوحة.',
            'data' => [
                'enabled' => $enabled,
            ],
        ]);
    }

    public function destroy(Request $request, string $identifier): JsonResponse
    {
        if ($request->user()?->role !== UserRole::Admin) {
            abort(403, 'Unauthorized for this area.');
        }

        $removed = $this->spotlight->remove($identifier);

        return response()->json([
            'ok' => $removed,
            'message' => $removed ? 'تمت الإزالة من السلايدر.' : 'العنصر غير موجود.',
        ], $removed ? 200 : 404);
    }

    public function clear(Request $request): JsonResponse
    {
        if ($request->user()?->role !== UserRole::Admin) {
            abort(403, 'Unauthorized for this area.');
        }

        $deleted = $this->spotlight->clear();

        return response()->json([
            'ok' => true,
            'message' => 'تم تفريغ السلايدر.',
            'meta' => ['deleted' => $deleted],
        ]);
    }

    protected function authorizeAccess(Request $request): void
    {
        $role = $request->user()?->role;

        if ($role !== UserRole::Admin && $role !== UserRole::Dealer) {
            abort(403, 'Unauthorized for this area.');
        }
    }
}
