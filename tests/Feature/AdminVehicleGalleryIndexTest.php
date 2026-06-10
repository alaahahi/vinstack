<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\VinstackSetting;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminVehicleGalleryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_fetch_keeps_vehicle_in_admin_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vin = '1HGBH41JXMN109186';
        $vinstackId = '507f1f77bcf86cd799439011';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'data' => [
                    'id' => $vinstackId,
                    'created_at' => '2019-01-01T00:00:00+00:00',
                    'status' => 'Delivered',
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/terminal-new.jpg'],
                    ],
                    'pickup' => [
                        'urls' => [],
                    ],
                    'destination' => [
                        'urls' => [],
                    ],
                ],
            ], 200),
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => $vinstackId,
            'vin' => $vin,
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'id' => $vinstackId,
                'created_at' => '2024-06-01T10:00:00+00:00',
            ],
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/vehicles/{$vehicle->id}/gallery")
            ->assertOk()
            ->assertJsonPath('data.gallery_fresh', true);

        $this->getJson('/api/admin/vehicles?source=vinstack')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $vehicle->id)
            ->assertJsonPath('data.0.vin', $vin);
    }
}
