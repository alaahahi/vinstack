<?php

namespace App\Services;

use App\Models\AuctionSpotlightItem;
use App\Models\User;
use App\Models\VinstackSetting;
use Illuminate\Support\Str;

class AuctionSpotlightService
{
    public const MAX_ITEMS = 16;

    public const MAX_THUMBS = 8;

    public function enabled(): bool
    {
        return (bool) (VinstackSetting::current()->auction_spotlight_enabled ?? true);
    }

    public function setEnabled(bool $enabled): bool
    {
        $settings = VinstackSetting::current();
        $settings->update(['auction_spotlight_enabled' => $enabled]);

        return (bool) $settings->fresh()->auction_spotlight_enabled;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return AuctionSpotlightItem::query()
            ->orderByDesc('last_viewed_at')
            ->orderByDesc('id')
            ->limit(self::MAX_ITEMS)
            ->get()
            ->map(fn (AuctionSpotlightItem $item) => $item->toApiArray())
            ->all();
    }

    /**
     * Persist a viewed vehicle snapshot for the shared search-page slider.
     * Does not call Apibara — uses payload already loaded on detail.
     *
     * @param  array<string, mixed>  $vehicle
     * @return array<string, mixed>|null
     */
    public function record(User $user, array $vehicle): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $identifier = $this->resolveIdentifier($vehicle);

        if ($identifier === '') {
            return null;
        }

        $existing = AuctionSpotlightItem::query()
            ->where('identifier', $identifier)
            ->first();

        $payload = $this->mapVehicle($vehicle, $identifier);

        if ($existing) {
            $existing->fill($payload);
            $existing->last_viewed_by = $user->id;
            $existing->last_viewed_at = now();
            $existing->view_count = (int) $existing->view_count + 1;
            $existing->save();
            $item = $existing;
        } else {
            $item = AuctionSpotlightItem::query()->create([
                ...$payload,
                'last_viewed_by' => $user->id,
                'last_viewed_at' => now(),
                'view_count' => 1,
            ]);
        }

        $this->trimExcess();

        return $item->toApiArray();
    }

    public function clear(): int
    {
        return AuctionSpotlightItem::query()->delete();
    }

    public function remove(string $identifier): bool
    {
        return AuctionSpotlightItem::query()
            ->where('identifier', trim($identifier))
            ->delete() > 0;
    }

    protected function trimExcess(): void
    {
        $keepIds = AuctionSpotlightItem::query()
            ->orderByDesc('last_viewed_at')
            ->orderByDesc('id')
            ->limit(self::MAX_ITEMS)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        AuctionSpotlightItem::query()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return array<string, mixed>
     */
    protected function mapVehicle(array $vehicle, string $identifier): array
    {
        $thumbs = $this->extractThumbs($vehicle);

        return [
            'identifier' => $identifier,
            'platform' => isset($vehicle['platform']) ? Str::lower((string) $vehicle['platform']) : null,
            'vin' => isset($vehicle['vin']) ? strtoupper(trim((string) $vehicle['vin'])) : null,
            'lot_number' => isset($vehicle['lot_number']) ? trim((string) $vehicle['lot_number']) : null,
            'title' => isset($vehicle['title']) ? trim((string) $vehicle['title']) : null,
            'year' => isset($vehicle['year']) ? (int) $vehicle['year'] : null,
            'make' => isset($vehicle['make']) ? trim((string) $vehicle['make']) : null,
            'model' => isset($vehicle['model']) ? trim((string) $vehicle['model']) : null,
            'thumb_urls' => $thumbs,
            'current_bid_usd' => data_get($vehicle, 'pricing.current_bid_usd')
                ?? $vehicle['current_bid_usd']
                ?? null,
            'location_display' => data_get($vehicle, 'location.display')
                ?? $vehicle['location_display']
                ?? null,
            'primary_damage' => data_get($vehicle, 'condition.primary_damage')
                ?? $vehicle['primary_damage']
                ?? null,
            'snapshot' => [
                'slug_vin' => $vehicle['slug_vin'] ?? null,
                'auction_at' => data_get($vehicle, 'auction.formatted')
                    ?? data_get($vehicle, 'auction.auction_at')
                    ?? $vehicle['ad']
                    ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $vehicle
     * @return list<string>
     */
    protected function extractThumbs(array $vehicle): array
    {
        $urls = [];

        if (! empty($vehicle['thumb_urls']) && is_array($vehicle['thumb_urls'])) {
            $urls = $vehicle['thumb_urls'];
        } elseif (! empty($vehicle['media']['thumbs']) && is_array($vehicle['media']['thumbs'])) {
            $urls = $vehicle['media']['thumbs'];
        } elseif (! empty($vehicle['media']['items']) && is_array($vehicle['media']['items'])) {
            foreach ($vehicle['media']['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $url = $item['thumb'] ?? $item['large'] ?? $item['full'] ?? null;
                if (is_string($url) && $url !== '') {
                    $urls[] = $url;
                }
            }
        } elseif (! empty($vehicle['thumb_url']) && is_string($vehicle['thumb_url'])) {
            $urls[] = $vehicle['thumb_url'];
        }

        return array_values(array_slice(array_unique(array_filter($urls, 'is_string')), 0, self::MAX_THUMBS));
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    protected function resolveIdentifier(array $vehicle): string
    {
        foreach (['vin', 'identifier', 'slug_vin', 'lot_number'] as $key) {
            if (! empty($vehicle[$key]) && is_scalar($vehicle[$key])) {
                return trim((string) $vehicle[$key]);
            }
        }

        return '';
    }
}
