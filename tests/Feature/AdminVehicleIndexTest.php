<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminVehicleIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_list_includes_unassigned_vinstack_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-unassigned-1',
            'vin' => '1HGCM82633A004352',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.source', 'vinstack');
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

    public function test_source_filter_returns_nujoom_al_jazeera_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Vehicle::query()->create([
            'source' => VehicleSource::NujoomAlJazeera,
            'vinstack_id' => 'nujoom-1',
            'vin' => '1HGCM82633A004359',
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-2',
            'vin' => '1HGCM82633A004360',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/vehicles?source=nujoom_al_jazeera');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.source', 'nujoom_al_jazeera');
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
}
