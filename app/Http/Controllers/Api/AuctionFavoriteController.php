<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAuctionFavoriteRequest;
use App\Services\AuctionFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionFavoriteController extends Controller
{
    public function __construct(
        protected AuctionFavoriteService $favorites,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);
        $user = $request->user();
        $isAdmin = $user->role === UserRole::Admin;

        return response()->json([
            'ok' => true,
            'data' => $this->favorites->listFor($user),
            'meta' => [
                'count' => $this->favorites->countFor($user),
                'scope' => $isAdmin ? 'all' : 'own',
            ],
        ]);
    }

    public function identifiers(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        return response()->json([
            'ok' => true,
            'data' => $this->favorites->identifiersFor($request->user()),
        ]);
    }

    public function store(StoreAuctionFavoriteRequest $request): JsonResponse
    {
        $favorite = $this->favorites->add($request->user(), $request->validated());

        return response()->json([
            'ok' => true,
            'message' => 'تمت الإضافة إلى المفضلة.',
            'data' => $favorite,
        ], 201);
    }

    public function destroy(Request $request, string $identifier): JsonResponse
    {
        $this->authorizeAccess($request);

        $ownerUserId = $request->user()?->role === UserRole::Admin
            ? ($request->integer('user_id') ?: null)
            : null;

        $removed = $this->favorites->remove(
            $request->user(),
            $identifier,
            $ownerUserId,
        );

        if (! $removed) {
            return response()->json([
                'ok' => false,
                'message' => 'السيارة غير موجودة في المفضلة.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'تمت الإزالة من المفضلة.',
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
