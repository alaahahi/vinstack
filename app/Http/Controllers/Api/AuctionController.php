<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Exceptions\ApibaraAuctionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchAuctionsRequest;
use App\Services\ApibaraAuctionService;
use App\Services\ApibaraUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function __construct(
        protected ApibaraAuctionService $auctions,
        protected ApibaraUsageService $usage,
    ) {}

    public function index(SearchAuctionsRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->auctions->search($request->filters()));
    }

    public function filters(Request $request): JsonResponse
    {
        $this->authorizeAuctionAccess($request);

        return $this->respond(fn () => $this->auctions->filters(
            $request->boolean('force_refresh'),
        ));
    }

    public function cacheStatus(SearchAuctionsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        unset($filters['force_refresh'], $filters['cache_only']);

        return response()->json([
            'ok' => true,
            'data' => [
                'available' => $this->auctions->hasSearchCache($filters),
            ],
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $this->authorizeAuctionAccess($request);

        return $this->respond(fn () => $this->auctions->test());
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $this->authorizeAuctionAccess($request);

        return $this->respond(fn () => $this->auctions->show(
            $identifier,
            $request->boolean('force_refresh'),
        ));
    }

    public function history(Request $request, string $identifier): JsonResponse
    {
        $this->authorizeAuctionAccess($request);

        return $this->respond(fn () => $this->auctions->history($identifier, [
            'per_page' => $request->query('per_page'),
            'cursor' => $request->query('cursor'),
        ], $request->boolean('force_refresh')));
    }

    public function usage(Request $request): JsonResponse
    {
        if ($request->user()?->role !== UserRole::Admin) {
            abort(403, 'Unauthorized for this area.');
        }

        $local = $this->usage->monthlySummary();
        $remote = null;

        if ($request->boolean('include_remote')) {
            try {
                $remotePayload = $this->auctions->remoteUsage($request->boolean('force_refresh'));
                $remote = $remotePayload['data'] ?? null;
            } catch (ApibaraAuctionException $e) {
                $remote = [
                    'error' => $e->getMessage(),
                    'code' => $e->errorCode(),
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'local' => $local,
                'remote' => $remote,
            ],
        ]);
    }

    protected function authorizeAuctionAccess(Request $request): void
    {
        $role = $request->user()?->role;

        if ($role !== UserRole::Admin && $role !== UserRole::Dealer) {
            abort(403, 'Unauthorized for this area.');
        }
    }

    /**
     * @param  callable(): array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}  $callback
     */
    protected function respond(callable $callback): JsonResponse
    {
        try {
            $payload = $callback();

            return response()->json([
                'ok' => $payload['ok'] ?? true,
                'data' => $payload['data'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'cached' => (bool) ($payload['cached'] ?? false),
            ]);
        } catch (ApibaraAuctionException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $e->status());
        }
    }
}
