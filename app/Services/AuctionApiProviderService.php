<?php

namespace App\Services;

use App\Exceptions\ApibaraAuctionException;
use App\Models\ApibaraRequestLog;
use App\Models\AuctionApiProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AuctionApiProviderService
{
    /**
     * Seed the first provider from .env at most once.
     * After an admin deletes all keys, listing/search must not recreate them.
     */
    public function ensureDefaultProvider(): void
    {
        if (AuctionApiProvider::query()->exists()) {
            return;
        }

        if ($this->envSeedAlreadyAttempted()) {
            return;
        }

        $apiKey = trim((string) config('apibara.api_key', ''));

        if ($apiKey === '') {
            $this->markEnvSeedAttempted();

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

        $this->markEnvSeedAttempted();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSummaries(): array
    {
        // Do not reseed here after an intentional wipe — deleted keys must stay gone.
        if (! $this->envSeedAlreadyAttempted()) {
            $this->ensureDefaultProvider();
        }

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
        if (! $this->envSeedAlreadyAttempted()) {
            $this->ensureDefaultProvider();
        }

        $active = AuctionApiProvider::query()->enabled()->active()->first()
            ?? AuctionApiProvider::query()->enabled()->orderBy('sort_order')->orderBy('id')->first();

        return $active ? $this->present($active) : null;
    }

    /**
     * @param  list<int>  $skipIds
     */
    public function resolveForLiveRequest(array $skipIds = []): AuctionApiProvider
    {
        if (! $this->envSeedAlreadyAttempted()) {
            $this->ensureDefaultProvider();
        }

        if (! AuctionApiProvider::query()->enabled()->exists()) {
            throw new ApibaraAuctionException(
                'لا يوجد مفتاح API للمزاد. أضف مفتاحاً من الإعدادات.',
                422,
                'apibara_no_provider',
            );
        }

        $active = AuctionApiProvider::query()
            ->enabled()
            ->active()
            ->when($skipIds !== [], fn ($q) => $q->whereNotIn('id', $skipIds))
            ->first();

        if ($active && $this->remaining($active) > 0) {
            return $active;
        }

        if ($active) {
            $this->markExhausted($active);
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
                'quota_exhausted_at' => $reason === 'manual' ? null : $provider->quota_exhausted_at,
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

    public function rotateIfExhausted(AuctionApiProvider $provider): void
    {
        if ($this->remaining($provider) > 0) {
            return;
        }

        $this->markExhausted($provider);

        if (! $provider->is_active) {
            return;
        }

        $next = $this->nextAvailable([$provider->id]);

        if ($next) {
            $this->activate($next, 'auto_quota');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): AuctionApiProvider
    {
        $this->markEnvSeedAttempted();

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
        $this->markEnvSeedAttempted();

        $wasActive = (bool) $provider->is_active;

        DB::transaction(function () use ($provider): void {
            // Detach usage logs first so FK / SQLite never blocks the delete.
            ApibaraRequestLog::query()
                ->where('provider_id', $provider->id)
                ->update(['provider_id' => null]);

            $provider->delete();
        });

        if ($wasActive) {
            $next = AuctionApiProvider::query()->enabled()->orderBy('sort_order')->orderBy('id')->first();

            if ($next) {
                $this->activate($next, 'manual');
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

    protected function envSeedMarkerPath(): string
    {
        return storage_path('app/auction-api-providers.seeded');
    }

    protected function envSeedAlreadyAttempted(): bool
    {
        return File::exists($this->envSeedMarkerPath());
    }

    protected function markEnvSeedAttempted(): void
    {
        $path = $this->envSeedMarkerPath();
        $dir = dirname($path);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (! File::exists($path)) {
            File::put($path, now()->toIso8601String().PHP_EOL);
        }
    }
}
