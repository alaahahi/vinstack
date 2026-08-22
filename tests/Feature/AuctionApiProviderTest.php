<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuctionApiProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuctionApiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'apibara.api_key' => '',
            'apibara.base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'apibara.timeout' => 10,
            'apibara.connect_timeout' => 5,
            'apibara.cache_ttl' => 86400,
        ]);
    }

    public function test_admin_can_add_and_manually_activate_providers(): void
    {
        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->postJson('/api/admin/auction-providers', [
            'name' => 'Apibara A',
            'base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'api_key' => 'key-a',
            'monthly_quota' => 80,
        ])->assertCreated()->assertJsonPath('data.is_active', true);

        $second = $this->postJson('/api/admin/auction-providers', [
            'name' => 'Apibara B',
            'base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'api_key' => 'key-b',
            'monthly_quota' => 120,
        ])->assertCreated();

        $secondId = $second->json('data.id');

        $this->postJson("/api/admin/auction-providers/{$secondId}/activate")
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.id', $secondId);

        $this->getJson('/api/admin/auction-providers')
            ->assertOk()
            ->assertJsonPath('data.active.id', $secondId)
            ->assertJsonPath('data.active.remaining', 120);
    }

    public function test_dealer_cannot_manage_auction_providers(): void
    {
        Sanctum::actingAs($this->makeUser(UserRole::Dealer));

        $this->getJson('/api/admin/auction-providers')->assertForbidden();
        $this->postJson('/api/admin/auction-providers', [
            'name' => 'Nope',
            'base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'api_key' => 'x',
        ])->assertForbidden();
    }

    public function test_switches_to_next_key_when_monthly_quota_is_finished(): void
    {
        $this->makeProvider('Apibara A', 'key-a', 1, true, 1);
        $this->makeProvider('Apibara B', 'key-b', 50, false, 2);

        Http::fake(function ($request) {
            $key = $this->requestApiKey($request);

            return Http::response([
                'ok' => true,
                'data' => [['vin' => $key === 'key-b' ? 'FROM-B' : 'FROM-A']],
                'meta' => null,
            ], 200);
        });

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions?make=Toyota&force_refresh=1')
            ->assertOk()
            ->assertJsonPath('data.0.vin', 'FROM-A');

        $this->getJson('/api/auctions?make=Honda&force_refresh=1')
            ->assertOk()
            ->assertJsonPath('data.0.vin', 'FROM-B');

        $this->getJson('/api/admin/auction-providers')
            ->assertOk()
            ->assertJsonPath('data.active.name', 'Apibara B');
    }

    public function test_switches_to_next_key_when_upstream_quota_is_rate_limited(): void
    {
        $this->makeProvider('Apibara A', 'key-a', 100, true, 1);
        $this->makeProvider('Apibara B', 'key-b', 100, false, 2);

        Http::fake(function ($request) {
            if ($this->requestApiKey($request) === 'key-a') {
                return Http::response(['message' => 'Too Many Requests'], 429);
            }

            return Http::response([
                'ok' => true,
                'data' => [['vin' => 'FROM-B']],
                'meta' => null,
            ], 200);
        });

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions?make=Kia&force_refresh=1')
            ->assertOk()
            ->assertJsonPath('data.0.vin', 'FROM-B');

        $this->assertTrue(
            AuctionApiProvider::query()->where('name', 'Apibara B')->value('is_active')
        );
    }

    public function test_usage_lists_who_spent_requests(): void
    {
        $this->makeProvider('Apibara A', 'key-a', 100, true, 1);

        Http::fake([
            'apibara.tech/*' => Http::response(['ok' => true, 'data' => [['vin' => 'X']], 'meta' => null], 200),
        ]);

        $admin = $this->makeUser(UserRole::Admin, 'Admin User');
        Sanctum::actingAs($admin);

        $this->getJson('/api/auctions?make=Ford')->assertOk();

        $this->getJson('/api/auctions/usage')
            ->assertOk()
            ->assertJsonPath('data.local.by_user.0.name', 'Admin User')
            ->assertJsonPath('data.local.by_user.0.role', 'admin')
            ->assertJsonPath('data.local.active_provider.name', 'Apibara A');
    }

    protected function makeProvider(
        string $name,
        string $apiKey,
        int $quota,
        bool $active,
        int $sortOrder,
    ): AuctionApiProvider {
        return AuctionApiProvider::query()->create([
            'name' => $name,
            'base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'api_key' => $apiKey,
            'monthly_quota' => $quota,
            'sort_order' => $sortOrder,
            'is_enabled' => true,
            'is_active' => $active,
        ]);
    }

    protected function makeUser(UserRole $role, string $name = 'Tester'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => $role,
        ]);
    }

    protected function requestApiKey($request): string
    {
        $key = $request->header('X-API-Key');

        if (is_array($key)) {
            return (string) ($key[0] ?? '');
        }

        return (string) $key;
    }
}
