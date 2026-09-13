<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Models\AutoshipperSetting;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutoshipperSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_autoshipper_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->getJson('/api/admin/autoshipper/settings')
            ->assertOk()
            ->assertJsonPath('data.sync_enabled', true)
            ->assertJsonPath('data.has_token', false);

        $this->putJson('/api/admin/autoshipper/settings', [
            'api_base_url' => 'https://autoshipper.io/api',
            'api_token' => 'future-token',
            'sync_enabled' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.sync_enabled', false)
            ->assertJsonPath('data.has_token', true);

        $settings = AutoshipperSetting::current();
        $this->assertFalse($settings->sync_enabled);
        $this->assertSame('future-token', $settings->api_token);
    }

    public function test_admin_can_trigger_sync(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
        AutoshipperSetting::current();

        Http::fake([
            '*/sync/vehicles' => Http::response([
                'data' => [[
                    'id' => 'AS-9',
                    'vin' => 'JH4KA7650MC000001',
                    'make' => 'Acura',
                    'model' => 'Legend',
                    'year' => 2020,
                ]],
            ]),
            '*/sync/media' => Http::response([
                'data' => [[
                    'vehicleId' => 'AS-9',
                    'url' => 'https://cdn.example.com/a.jpg',
                ]],
            ]),
            '*/sync/vehicleCharges' => Http::response(['data' => []]),
        ]);

        $this->postJson('/api/admin/autoshipper/sync')
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('total', 1);

        $this->assertDatabaseHas('vehicles', [
            'autoshipper_id' => 'AS-9',
            'source' => VehicleSource::AutoShipper->value,
            'vin' => 'JH4KA7650MC000001',
        ]);
    }

    public function test_dealer_cannot_access_autoshipper_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Dealer]));

        $this->getJson('/api/admin/autoshipper/settings')->assertForbidden();
        $this->putJson('/api/admin/autoshipper/settings', ['sync_enabled' => false])->assertForbidden();
        $this->postJson('/api/admin/autoshipper/sync')->assertForbidden();
    }

    public function test_command_skips_when_sync_disabled(): void
    {
        AutoshipperSetting::current()->update(['sync_enabled' => false]);

        $this->artisan('autoshipper:sync')
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        $this->assertSame(0, Vehicle::query()->count());
    }
}
