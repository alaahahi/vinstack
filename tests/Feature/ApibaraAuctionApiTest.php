<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApibaraAuctionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'apibara.api_key' => 'test-key-not-real',
            'apibara.base_url' => 'https://apibara.tech/api/v1/vehicle-auction',
            'apibara.timeout' => 10,
            'apibara.connect_timeout' => 5,
        ]);
    }

    public function test_guest_cannot_access_auctions(): void
    {
        $this->getJson('/api/auctions/test')->assertUnauthorized();
    }

    public function test_admin_can_run_auction_test_endpoint(): void
    {
        Http::fake([
            'apibara.tech/*' => Http::response([
                'ok' => true,
                'data' => [
                    [
                        'vin' => '4T1NZ1AK9LU034545',
                        'platform' => 'copart',
                        'lot_number' => '60799856',
                        'make' => 'TOYOTA',
                        'model' => 'CAMRY',
                        'year' => 2020,
                        'pricing' => ['current_bid_usd' => 3750],
                    ],
                ],
                'meta' => [
                    'per_page' => 10,
                    'next_cursor' => 'CURSOR',
                    'prev_cursor' => null,
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/test')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.0.make', 'TOYOTA')
            ->assertJsonPath('meta.next_cursor', 'CURSOR');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/vehicles')
                && $request->hasHeader('X-API-Key', 'test-key-not-real')
                && $request['platform'] === 'copart'
                && $request['make'] === 'Toyota'
                && $request['model'] === 'Camry';
        });
    }

    public function test_dealer_can_search_and_maps_state_and_vin(): void
    {
        Http::fake([
            'apibara.tech/*' => Http::response(['ok' => true, 'data' => [], 'meta' => null], 200),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Dealer));

        $this->getJson('/api/auctions?platform=iaai&vin=ABC123&state=fl&per_page=10')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('cached', false);

        Http::assertSent(function ($request) {
            return $request['platform'] === 'iaai'
                && $request['s'] === 'ABC123'
                && $request['loc_state'] === 'FL'
                && (int) $request['per_page'] === 10;
        });
    }

    public function test_identical_search_is_served_from_cache(): void
    {
        Http::fake([
            'apibara.tech/*' => Http::response([
                'ok' => true,
                'data' => [['vin' => 'X']],
                'meta' => null,
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions?platform=copart&make=Toyota&per_page=10')
            ->assertOk()
            ->assertJsonPath('cached', false);

        $this->getJson('/api/auctions?platform=copart&make=Toyota&per_page=10')
            ->assertOk()
            ->assertJsonPath('cached', true);

        Http::assertSentCount(1);

        $this->assertDatabaseCount('apibara_request_logs', 2);
        $this->assertDatabaseHas('apibara_request_logs', [
            'billed' => true,
            'cached' => false,
        ]);
        $this->assertDatabaseHas('apibara_request_logs', [
            'billed' => false,
            'cached' => true,
        ]);
    }

    public function test_usage_endpoint_returns_local_summary(): void
    {
        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/usage')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.local.free_quota', 100)
            ->assertJsonPath('data.local.max_per_page', 10);
    }

    public function test_dealer_cannot_access_usage_endpoint(): void
    {
        Sanctum::actingAs($this->makeUser(UserRole::Dealer));

        $this->getJson('/api/auctions/usage')->assertForbidden();
    }

    public function test_maps_401_to_friendly_message(): void
    {
        Http::fake([
            'apibara.tech/*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/test')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('code', 'apibara_unauthorized');
    }

    public function test_show_and_history_routes(): void
    {
        Http::fake([
            'apibara.tech/*/vehicles/4T1NZ1AK9LU034545/history*' => Http::response([
                'ok' => true,
                'data' => [['platform' => 'copart', 'lot_number' => '1']],
            ], 200),
            'apibara.tech/*/vehicles/4T1NZ1AK9LU034545' => Http::response([
                'ok' => true,
                'data' => ['vin' => '4T1NZ1AK9LU034545', 'lot_number' => '60799856'],
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/4T1NZ1AK9LU034545')
            ->assertOk()
            ->assertJsonPath('data.vin', '4T1NZ1AK9LU034545');

        $this->getJson('/api/auctions/4T1NZ1AK9LU034545/history')
            ->assertOk()
            ->assertJsonPath('data.0.lot_number', '1');
    }

    public function test_show_falls_back_from_slug_vin_to_vin_on_404(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '2026-kia-k4-lxs-3KPFT4DE8TE273956')) {
                return Http::response(['ok' => false, 'message' => 'Not found'], 404);
            }

            if (str_contains($url, '3KPFT4DE8TE273956')) {
                return Http::response([
                    'ok' => true,
                    'data' => [
                        'vin' => '3KPFT4DE8TE273956',
                        'title' => '2026 KIA K4 LXS',
                    ],
                ], 200);
            }

            return Http::response(['ok' => false], 404);
        });

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/2026-kia-k4-lxs-3KPFT4DE8TE273956')
            ->assertOk()
            ->assertJsonPath('data.vin', '3KPFT4DE8TE273956');
    }

    public function test_filters_endpoint_is_cached(): void
    {
        Http::fake([
            'apibara.tech/*/vehicles/filters' => Http::response([
                'ok' => true,
                'data' => [
                    'make_model' => [
                        'makes' => ['TOYOTA', 'HONDA'],
                        'models_by_make' => [
                            'TOYOTA' => ['CAMRY', 'COROLLA'],
                        ],
                    ],
                    'types' => [
                        ['id' => 1, 'name' => 'SEDAN'],
                    ],
                    'lot' => [
                        'status' => [
                            ['value' => 'All', 'label' => 'All'],
                        ],
                        'sub_status' => [
                            ['value' => 'Open', 'label' => 'Open'],
                        ],
                        'defaults' => [
                            'lot_status' => 'All',
                            'lot_sub_status' => 'Open',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser(UserRole::Admin));

        $this->getJson('/api/auctions/filters')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.make_model.makes.0', 'TOYOTA')
            ->assertJsonPath('cached', false);

        $this->getJson('/api/auctions/filters')
            ->assertOk()
            ->assertJsonPath('data.make_model.makes.0', 'TOYOTA')
            ->assertJsonPath('cached', true);

        Http::assertSentCount(1);
    }

    protected function makeUser(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
        ]);
    }
}
