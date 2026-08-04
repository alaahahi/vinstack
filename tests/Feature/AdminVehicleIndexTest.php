<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\VehicleUploadedImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminVehicleIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->mock(VehicleUploadedImageService::class, function ($mock): void {
            $mock->shouldReceive('enrichListVehicle')
                ->andReturnUsing(fn (Vehicle $vehicle) => $vehicle->toArray());
        });
    }

    public function test_default_list_includes_unassigned_vinstack_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-unassigned-1',
            'vin' => '1HGCM82633A004352',
            'eta' => '2026-08-15',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'vinstack')
            ->assertJsonPath('data.0.eta', '2026-08-15');
    }

    public function test_legacy_imported_status_filter_maps_to_vinstack_source(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-1',
            'vin' => '1HGCM82633A004353',
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-1',
            'vin' => '1HGCM82633A004354',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles?status=imported');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.source', 'vinstack');
    }

    public function test_source_filter_returns_vinstack_vehicles_without_dealer_assignment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'معرض الاختبار',
        ]);

        $unassigned = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-open-1',
            'vin' => '1HGCM82633A004355',
            'status' => VehicleStatus::Available,
        ]);

        $assigned = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-assigned-1',
            'vin' => '1HGCM82633A004356',
            'status' => VehicleStatus::Assigned,
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $assigned->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles?source=vinstack');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);

        $vins = collect($response->json('data'))->pluck('vin')->all();

        $this->assertContains($unassigned->vin, $vins);
        $this->assertContains($assigned->vin, $vins);
    }

    public function test_dealer_filter_only_shows_assigned_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'معرض الاختبار',
        ]);

        $unassigned = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-open-2',
            'vin' => '1HGCM82633A004357',
            'status' => VehicleStatus::Available,
        ]);

        $assigned = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-assigned-2',
            'vin' => '1HGCM82633A004358',
            'status' => VehicleStatus::Assigned,
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $assigned->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/vehicles?dealer_id={$dealer->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.vin', $assigned->vin);

        $this->assertNotContains($unassigned->vin, collect($response->json('data'))->pluck('vin')->all());
    }

    public function test_default_list_orders_by_purchase_date_then_created_at(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $olderPurchase = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-order-1',
            'vin' => '1HGCM82633A004361',
            'status' => VehicleStatus::Available,
            'price' => 9000,
            'raw_data' => [
                'purchase_date' => '2026-06-01',
                'value' => '8500',
            ],
        ]);

        $newerPurchase = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-order-2',
            'vin' => '1HGCM82633A004362',
            'status' => VehicleStatus::Available,
            'price' => 12500.50,
            'raw_data' => [
                'purchase_date' => '2026-07-01',
                'value' => '12000',
            ],
        ]);

        $fallbackCreated = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-order-3',
            'vin' => '1HGCM82633A004363',
            'status' => VehicleStatus::Available,
            'price' => 15000,
            'raw_data' => [],
        ]);

        $olderPurchase->update(['created_at' => now()->subDays(3)]);
        $newerPurchase->update(['created_at' => now()->subDays(2)]);
        $fallbackCreated->update(['created_at' => now()]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles?source=manual&per_page=50');

        $response->assertOk();

        $vins = collect($response->json('data'))->pluck('vin')->all();

        $this->assertSame([
            $fallbackCreated->vin,
            $newerPurchase->vin,
            $olderPurchase->vin,
        ], $vins);

        $byVin = collect($response->json('data'))->keyBy('vin');

        $this->assertSame('12500.50', $byVin[$newerPurchase->vin]['price']);
        $this->assertSame('12000', $byVin[$newerPurchase->vin]['raw_data']['value']);
        $this->assertSame('15000.00', $byVin[$fallbackCreated->vin]['price']);
    }

    public function test_admin_vehicle_list_reuses_cached_payload_for_same_query(): void
    {
        $this->mock(VehicleUploadedImageService::class, function ($mock): void {
            $mock->shouldReceive('enrichListVehicle')
                ->once()
                ->andReturnUsing(fn (Vehicle $vehicle) => $vehicle->toArray());
        });

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-cache-1',
            'vin' => '1HGCM82633A004364',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $uri = '/api/admin/vehicles?search=1HGCM82633A004364&per_page=50&page=1';

        $this->getJson($uri)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson($uri)->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_admin_vehicle_list_cache_expires_after_five_minutes(): void
    {
        $this->mock(VehicleUploadedImageService::class, function ($mock): void {
            $mock->shouldReceive('enrichListVehicle')
                ->twice()
                ->andReturnUsing(fn (Vehicle $vehicle) => $vehicle->toArray());
        });

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-cache-ttl-1',
            'vin' => '1HGCM82633A004365',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $uri = '/api/admin/vehicles?source=manual';

        $this->getJson($uri)->assertOk()->assertJsonPath('meta.total', 1);

        $this->travel(6)->minutes();

        $this->getJson($uri)->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_admin_vehicle_list_cache_is_invalidated_when_vehicle_data_changes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-cache-bust-1',
            'vin' => '1HGCM82633A004366',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $uri = '/api/admin/vehicles?source=vinstack';

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-cache-bust-2',
            'vin' => '1HGCM82633A004367',
            'status' => VehicleStatus::Available,
        ]);

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_vehicle_list_cache_is_invalidated_when_assignment_changes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Dealer Cache Test',
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-cache-assignment-1',
            'vin' => '1HGCM82633A004368',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $uri = "/api/admin/vehicles?dealer_id={$dealer->id}";

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.vin', $vehicle->vin);
    }
}
