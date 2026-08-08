<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\DealerNotificationLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStatusNotification;
use App\Models\VehicleUploadedImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_dashboard_stats(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $withUpload = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dash-1',
            'vin' => '1HGCM82633A004401',
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'loading_point' => 'Houston',
                'status' => 'At terminal',
            ],
        ]);
        $withUpload->forceFill(['created_at' => now()->subDays(2)])->save();

        VehicleUploadedImage::query()->create([
            'vehicle_id' => $withUpload->id,
            'stage' => 'terminal',
            'path' => 'vehicles/dash-1.jpg',
            'original_name' => 'dash-1.jpg',
            'uploaded_by' => $admin->id,
        ]);

        $withGallery = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'dash-2',
            'vin' => '1HGCM82633A004402',
            'status' => VehicleStatus::Available,
            'images' => ['https://cdn.example.com/car.jpg'],
            'raw_data' => [
                'loading_point' => 'New York',
                'status' => 'Loaded',
            ],
        ]);
        $withGallery->forceFill(['created_at' => now()->subMonths(2)])->save();

        $oldVehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dash-3',
            'vin' => '1HGCM82633A004403',
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'loading_point' => 'Houston',
                'status' => 'New Purchase',
            ],
        ]);
        $oldVehicle->forceFill(['created_at' => now()->subMonths(8)])->save();

        VehicleStatusNotification::query()->create([
            'vehicle_id' => $withUpload->id,
            'previous_status' => 'New Purchase',
            'new_status' => 'At terminal',
            'source' => 'sync',
        ]);

        DealerNotificationLog::query()->create([
            'phone' => '9647500000000',
            'message' => 'Test WA',
            'channel' => 'whatsapp',
            'source' => 'manual',
            'event' => 'dealer.manual_notification',
            'wa_queue_id' => 12,
            'wa_queue_status' => 'queued',
        ]);

        DealerNotificationLog::query()->create([
            'phone' => '9647500000001',
            'message' => 'Failed WA',
            'channel' => 'whatsapp',
            'source' => 'manual',
            'event' => 'dealer.vehicle_updated',
            'error_message' => 'timeout',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.totals.vehicles', 3)
            ->assertJsonPath('data.photos.with_photos', 2)
            ->assertJsonPath('data.photos.without_photos', 1)
            ->assertJsonPath('data.photos.with_uploaded', 1)
            ->assertJsonPath('data.photos.without_uploaded', 2)
            ->assertJsonPath('data.notifications.status_changes.unread', 1)
            ->assertJsonPath('data.whatsapp.total', 2)
            ->assertJsonPath('data.whatsapp.success', 1)
            ->assertJsonPath('data.whatsapp.failed', 1);

        $months = collect($response->json('data.vehicles_added.months'));
        $this->assertCount(6, $months);
        $this->assertSame(2, (int) $response->json('data.vehicles_added.total'));

        $houston = collect($response->json('data.loading_points'))->firstWhere('name', 'Houston');
        $this->assertNotNull($houston);
        $this->assertSame(2, $houston['total']);

        $statusCounts = collect($houston['statuses'])->keyBy('key');
        $this->assertSame(1, $statusCounts['at_terminal']['count']);
        $this->assertSame(1, $statusCounts['new_purchase']['count']);
    }

    public function test_dealer_cannot_load_admin_dashboard(): void
    {
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($dealer);

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }
}
