<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\AccountingExportSetting;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_export_when_vehicle_is_not_assigned(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        AccountingExportSetting::current()->update([
            'api_base_url' => 'https://copart.test',
            'api_token' => 'secret-token',
            'enabled' => true,
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'ACCTTESTVIN000001',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2024,
            'status' => VehicleStatus::Available,
        ]);

        $this->postJson("/api/admin/vehicles/{$vehicle->id}/export-to-accounting")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'يجب إسناد السيارة لتاجر قبل التصدير للمحاسبة.']);
    }

    public function test_rejects_export_when_dealer_has_no_phone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        AccountingExportSetting::current()->update([
            'api_base_url' => 'https://copart.test',
            'api_token' => 'secret-token',
            'enabled' => true,
        ]);

        $dealerUser = User::factory()->create([
            'role' => UserRole::Dealer,
            'phone' => null,
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'No Phone Co',
            'phone' => null,
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'ACCTTESTVIN000002',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2023,
            'status' => VehicleStatus::Assigned,
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/vehicles/{$vehicle->id}/export-to-accounting")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'هاتف التاجر مطلوب لربط الحسابات. أضف هاتفاً للتاجر أولاً.']);
    }

    public function test_exports_assigned_vehicle_to_copart_and_stores_status(): void
    {
        Http::fake([
            'https://copart.test/api/integration/vinstack/vehicles' => Http::response([
                'ok' => true,
                'data' => [
                    'queued' => true,
                    'import_id' => 77,
                    'status' => 'pending',
                    'vin' => 'ACCTTESTVIN000003',
                ],
            ], 202),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        AccountingExportSetting::current()->update([
            'api_base_url' => 'https://copart.test',
            'api_token' => 'secret-token',
            'enabled' => true,
        ]);

        $dealerUser = User::factory()->create([
            'role' => UserRole::Dealer,
            'name' => 'Dealer One',
            'phone' => '07501112233',
        ]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Showroom One',
            'phone' => '07501112233',
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'ACCTTESTVIN000003',
            'make' => 'Nissan',
            'model' => 'Sentra',
            'year' => 2025,
            'status' => VehicleStatus::Assigned,
            'raw_data' => [
                'lot' => '12345',
                'auction' => 'Copart',
                'purchase_date' => '2026-08-01',
            ],
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->postJson("/api/admin/vehicles/{$vehicle->id}/export-to-accounting")
            ->assertOk()
            ->assertJsonPath('data.accounting_export_status', 'pending_approval');

        $vehicle->refresh();
        $this->assertSame('pending_approval', $vehicle->accounting_export_status);
        $this->assertSame(77, $vehicle->copart_car_id);
        $this->assertNotNull($vehicle->exported_to_accounting_at);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://copart.test/api/integration/vinstack/vehicles'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['vin'] === 'ACCTTESTVIN000003'
                && $request['dealer']['phone'] === '07501112233';
        });
    }

    public function test_can_update_accounting_export_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/accounting-export/settings', [
            'api_base_url' => 'https://erp.example.com',
            'api_token' => 'new-token',
            'enabled' => true,
        ])->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.has_token', true);

        $settings = AccountingExportSetting::current();
        $this->assertTrue($settings->enabled);
        $this->assertSame('https://erp.example.com', $settings->api_base_url);
        $this->assertSame('new-token', $settings->api_token);
    }
}
