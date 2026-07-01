<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleGalleryReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_gallery_images_within_stage(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'REORDERVIN1234567',
            'status' => VehicleStatus::Available,
            'images' => [
                'https://cdn.example.com/a.jpg',
                'https://cdn.example.com/b.jpg',
            ],
            'raw_data' => [
                'images_by_stage' => [
                    'terminal' => [
                        'https://cdn.example.com/a.jpg',
                        'https://cdn.example.com/b.jpg',
                    ],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/vehicles/'.$vehicle->id.'/gallery/order', [
            'stage' => 'terminal',
            'urls' => [
                'https://cdn.example.com/b.jpg',
                'https://cdn.example.com/a.jpg',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.images_by_stage.terminal.0', 'https://cdn.example.com/b.jpg')
            ->assertJsonPath('data.images_by_stage.terminal.1', 'https://cdn.example.com/a.jpg');

        $vehicle->refresh();

        $this->assertSame(
            ['https://cdn.example.com/b.jpg', 'https://cdn.example.com/a.jpg'],
            $vehicle->raw_data['images_by_stage']['terminal'],
        );
    }

    public function test_reorder_rejects_urls_that_do_not_match_stage_gallery(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'REORDERVIN7654321',
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'images_by_stage' => [
                    'terminal' => ['https://cdn.example.com/a.jpg'],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/vehicles/'.$vehicle->id.'/gallery/order', [
            'stage' => 'terminal',
            'urls' => ['https://cdn.example.com/missing.jpg'],
        ])->assertStatus(422);
    }

    public function test_dealer_cannot_reorder_gallery(): void
    {
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'DEALERREORDERVIN1',
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'images_by_stage' => [
                    'terminal' => ['https://cdn.example.com/a.jpg'],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ]);

        Sanctum::actingAs($dealerUser);

        $this->putJson('/api/admin/vehicles/'.$vehicle->id.'/gallery/order', [
            'stage' => 'terminal',
            'urls' => ['https://cdn.example.com/a.jpg'],
        ])->assertForbidden();
    }
}
