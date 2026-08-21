<?php

namespace App\Services;

use App\Models\AuctionFavorite;
use App\Models\User;
use Illuminate\Support\Str;

class AuctionFavoriteService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listFor(User $user): array
    {
        return $user->auctionFavorites()
            ->latest('id')
            ->get()
            ->map(fn (AuctionFavorite $favorite) => $favorite->toApiArray())
            ->all();
    }

    /**
     * @return list<string>
     */
    public function identifiersFor(User $user): array
    {
        return $user->auctionFavorites()
            ->pluck('identifier')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    public function isFavorite(User $user, string $identifier): bool
    {
        return $user->auctionFavorites()
            ->where('identifier', $this->normalizeIdentifier($identifier))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return array<string, mixed>
     */
    public function add(User $user, array $vehicle): array
    {
        $identifier = $this->resolveIdentifier($vehicle);

        $favorite = AuctionFavorite::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'identifier' => $identifier,
            ],
            $this->mapVehicle($vehicle, $identifier),
        );

        return $favorite->toApiArray();
    }

    public function remove(User $user, string $identifier): bool
    {
        $deleted = $user->auctionFavorites()
            ->where('identifier', $this->normalizeIdentifier($identifier))
            ->delete();

        return $deleted > 0;
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return array<string, mixed>
     */
    protected function mapVehicle(array $vehicle, string $identifier): array
    {
        $thumb = $vehicle['thumb_url']
            ?? data_get($vehicle, 'media.thumbs.0')
            ?? data_get($vehicle, 'media.items.0.thumb')
            ?? null;

        return [
            'identifier' => $identifier,
            'platform' => isset($vehicle['platform']) ? Str::lower((string) $vehicle['platform']) : null,
            'vin' => isset($vehicle['vin']) ? strtoupper(trim((string) $vehicle['vin'])) : null,
            'lot_number' => isset($vehicle['lot_number']) ? trim((string) $vehicle['lot_number']) : null,
            'title' => isset($vehicle['title']) ? trim((string) $vehicle['title']) : null,
            'year' => isset($vehicle['year']) ? (int) $vehicle['year'] : null,
            'make' => isset($vehicle['make']) ? trim((string) $vehicle['make']) : null,
            'model' => isset($vehicle['model']) ? trim((string) $vehicle['model']) : null,
            'thumb_url' => is_string($thumb) ? $thumb : null,
            'current_bid_usd' => data_get($vehicle, 'pricing.current_bid_usd')
                ?? $vehicle['current_bid_usd']
                ?? null,
            'buy_now_usd' => data_get($vehicle, 'pricing.buy_now_usd')
                ?? $vehicle['buy_now_usd']
                ?? null,
            'location_display' => data_get($vehicle, 'location.display')
                ?? $vehicle['location_display']
                ?? null,
            'primary_damage' => data_get($vehicle, 'condition.primary_damage')
                ?? $vehicle['primary_damage']
                ?? null,
            'auction_at' => data_get($vehicle, 'auction.formatted')
                ?? data_get($vehicle, 'auction.auction_at')
                ?? $vehicle['auction_at']
                ?? $vehicle['ad']
                ?? null,
            'snapshot' => $vehicle,
        ];
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    protected function resolveIdentifier(array $vehicle): string
    {
        foreach (['slug_vin', 'identifier', 'vin', 'lot_number'] as $key) {
            if (! empty($vehicle[$key]) && is_scalar($vehicle[$key])) {
                return $this->normalizeIdentifier((string) $vehicle[$key]);
            }
        }

        abort(422, 'Vehicle identifier is required.');
    }

    protected function normalizeIdentifier(string $identifier): string
    {
        return trim($identifier);
    }
}
