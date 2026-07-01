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

class AdminVehicleDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_unassigned_vehicle(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-delete-1',
            'vin' => '1HGCM82633A004352',
            'status' => VehicleStatus::Available,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/vehicles/{$vehicle->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'تم حذف السيارة.',
            ]);

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    public function test_admin_cannot_delete_assigned_vehicle(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'معرض الاختبار',
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'manual-delete-1',
            'vin' => '1HGCM82633A004353',
            'status' => VehicleStatus::Assigned,
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/vehicles/{$vehicle->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'لا يمكن حذف السيارة لأنها مسندة لتاجر.',
            ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'deleted_at' => null,
        ]);
    }
}
