<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminVehicleIndexCache
{
    public const TTL_SECONDS = 300;

    private const VERSION_KEY = 'admin_vehicle_index_cache_version';

    /**
     * @param  callable(): array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}  $resolver
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function remember(Request $request, User $user, callable $resolver): array
    {
        return Cache::remember(
            $this->keyFor($request, $user),
            now()->addSeconds(self::TTL_SECONDS),
            $resolver,
        );
    }

    public static function bumpVersion(): void
    {
        Cache::add(self::VERSION_KEY, 1);
        Cache::increment(self::VERSION_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedParameters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'status' => (string) $request->input('status', ''),
            'source' => (string) $request->input('source', ''),
            'dealer_id' => max(0, (int) $request->input('dealer_id', 0)),
            'dealer_name' => $request->string('dealer_name')->trim()->toString(),
            'sort_field' => (string) $request->input('sort_field', ''),
            'sort_order' => $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc',
            'per_page' => min((int) $request->input('per_page', 50), 100),
            'page' => max(1, (int) $request->input('page', 1)),
        ];
    }

    private function keyFor(Request $request, User $user): string
    {
        $payload = [
            'version' => (int) Cache::get(self::VERSION_KEY, 1),
            'user_id' => $user->getKey(),
            'user_role' => $user->role?->value ?? (string) $user->role,
            'params' => $this->normalizedParameters($request),
        ];

        return 'admin_vehicles:index:'.hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
