<?php

namespace App\Services;

use App\Exceptions\ApibaraAuctionException;
use App\Models\ApibaraRequestLog;
use App\Models\AuctionApiProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuctionApiProviderService
{
    public function ensureDefaultProvider(): void
    {
        if (AuctionApiProvider::query()->exists()) {
            return;
        }

        $apiKey = trim((string) config('apibara.api_key', ''));

        if ($apiKey === '') {
            return;
        }

        AuctionApiProvider::query()->create([
            'name' => 'Apibara 1',
            'base_url' => rtrim((string) config('apibara.base_url', 'https://apibara.tech/api/v1/vehicle-auction'), '/'),
            'api_key' => $apiKey,
            'monthly_quota' => max(1, (int) config('apibara.monthly_free_quota', 100)),
            'sort_order' => 1,
            'is_enabled' => true,
            'is_active' => true,
            'last_switched_at' => now(),
            'last_switch_reason' => 'seed',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSummaries(): array
    {
        $this->ensureDefaultProvider();

        return AuctionApiProvider::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (AuctionApiProvider $provider) => $this->present($provider))
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeSummary(): ?array
    {
        $this->ensureDefaultProvider();

        $active = AuctionApiProvider::query()->enabled()->active()->first()
            ?? AuctionApiProvider::query()->enabled()->orderBy('sort_order')->orderBy('id')->first();

        return $active ? $this->present($active) : null;
    }

    /**
     * @param  list<int>  $skipIds
     */
    public function resolveForLiveRequest(array $skipIds = []): AuctionApiProvider
    {
        $this->ensureDefaultProvider();

        $active = AuctionApiProvider::query()
            ->enabled()
            ->active()
            ->when($skipIds !== [], fn ($q) => $q->whereNotIn('id', $skipIds))
            ->first();

        if ($active && $this->remaining($active) > 0) {
            return $active;
        }

        $next = $this->nextAvailable($skipIds);

        if ($next) {
            $this->activate($next, 'auto_quota');

            return $next;
        }

        throw new ApibaraAuctionException(
            'نفدت حصة كل مفاتيح API المزاد لهذا الشهر. أضف مفتاحاً جديداً من الإعدادات أو انتظر بداية الشهر.',
            429,
            'apibara_quota_exhausted',
        );
    }

    public function activate(AuctionApiProvider $provider, string $reason = 'manual'): AuctionApiProvider
    {
        if (! $provider->is_enabled) {
            $provider->is_enabled = true;
        }

        DB::transaction(function () use ($provider, $reason) {
            AuctionApiProvider::query()->where('id', '!=', $provider->id)->update(['is_active' => false]);

            $provider->forceFill([
                'is_active' => true,
                'is_enabled' => true,
                'last_switched_at' => now(),
                'last_switch_reason' => $reason,
            ])->save();
        });

        return $provider->fresh() ?? $provider;
    }

    public function markExhausted(AuctionApiProvider $provider): void
    {
        $provider->forceFill([
            'quota_exhausted_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): AuctionApiProvider
    {
        $this->ensureDefaultProvider();

        $activate = (bool) ($data['activate'] ?? false);
        unset($data['activate']);

        $provider = AuctionApiProvider::query()->create([
            'name' => trim((string) $data['name']),
            'base_url' => rtrim((string) $data['base_url'], '/'),
            'api_key' => trim((string) $data['api_key']),
            'monthly_quota' => max(1, (int) ($data['monthly_quota'] ?? config('apibara.monthly_free_quota', 100))),
            'sort_order' => (int) ($data['sort_order'] ?? ((int) AuctionApiProvider::query()->max('sort_order') + 1)),
            'is_enabled' => array_key_exists('is_enabled', $data) ? (bool) $data['is_enabled'] : true,
            'is_active' => false,
        ]);

        if ($activate || ! AuctionApiProvider::query()->active()->exists()) {
            $this->activate($provider, $activate ? 'manual' : 'seed');
        }

        return $provider->fresh() ?? $provider;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AuctionApiProvider $provider, array $data): AuctionApiProvider
    {
        if (array_key_exists('api_key', $data) && trim((string) $data['api_key']) === '') {
            unset($data['api_key']);
        }

        if (isset($data['base_url'])) {
            $data['base_url'] = rtrim((string) $data['base_url'], '/');
        }

        if (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);
        }

        $provider->fill($data)->save();

        return $provider->fresh() ?? $provider;
    }

    public function delete(AuctionApiProvider $provider): void
    {
        $wasActive = $provider->is_active;
        $provider->delete();

        if ($wasActive) {
            $next = AuctionApiProvider::query()->enabled()->orderBy('sort_order')->orderBy('id')->first();

            if ($next) {
                $this->activate($next, 'auto_quota');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AuctionApiProvider $provider): array
    {
        $billed = $this->billedThisMonth($provider);
        $remaining = $this->remaining($provider);

        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'base_url' => $provider->base_url,
            'has_key' => filled($provider->api_key),
            'key_hint' => $this->keyHint($provider->api_key),
            'monthly_quota' => (int) $provider->monthly_quota,
            'billed' => $billed,
            'remaining' => $remaining,
            'is_enabled' => (bool) $provider->is_enabled,
            'is_active' => (bool) $provider->is_active,
            'sort_order' => (int) $provider->sort_order,
            'last_switched_at' => $provider->last_switched_at?->toIso8601String(),
            'last_switch_reason' => $provider->last_switch_reason,
        ];
    }

    public function billedThisMonth(AuctionApiProvider $provider, ?Carbon $month = null): int
    {
        $month = ($month ?? now())->copy()->startOfMonth();

        return ApibaraRequestLog::query()
            ->where('provider_id', $provider->id)
            ->where('billed', true)
            ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
            ->count();
    }

    public function remaining(AuctionApiProvider $provider): int
    {
        if ($this->isExhaustedThisMonth($provider)) {
            return 0;
        }

        return max(0, (int) $provider->monthly_quota - $this->billedThisMonth($provider));
    }

    public function isExhaustedThisMonth(AuctionApiProvider $provider): bool
    {
        if ($provider->quota_exhausted_at && $provider->quota_exhausted_at->isSameMonth(now())) {
            return true;
        }

        return $this->billedThisMonth($provider) >= (int) $provider->monthly_quota;
    }

    /**
     * @param  list<int>  $skipIds
     */
    protected function nextAvailable(array $skipIds = []): ?AuctionApiProvider
    {
        $candidates = AuctionApiProvider::query()
            ->enabled()
            ->when($skipIds !== [], fn ($q) => $q->whereNotIn('id', $skipIds))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($candidates as $provider) {
            if ($this->remaining($provider) > 0) {
                return $provider;
            }
        }

        return null;
    }

    protected function keyHint(?string $apiKey): string
    {
        $apiKey = trim((string) $apiKey);

        if ($apiKey === '') {
            return '';
        }

        $tail = substr($apiKey, -4);

        return '••••'.$tail;
    }
}
