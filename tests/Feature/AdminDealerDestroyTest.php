<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDealerDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_dealer_with_active_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'معرض الاختبار',
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'test-vin-1',
            'vin' => '1HGCM82633A004352',
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/dealers/{$dealer->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'لا يمكن حذف التاجر لأنه مرتبط بسيارات',
            ]);

        $this->assertDatabaseHas('dealers', ['id' => $dealer->id]);
        $this->assertDatabaseHas('users', ['id' => $dealerUser->id]);
    }

    public function test_admin_can_delete_dealer_without_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'معرض فارغ',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/dealers/{$dealer->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'تم حذف التاجر.',
            ]);

        $this->assertDatabaseMissing('dealers', ['id' => $dealer->id]);
        $this->assertDatabaseMissing('users', ['id' => $dealerUser->id]);
    }
}
