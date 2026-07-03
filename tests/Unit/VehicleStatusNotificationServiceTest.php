<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleStatusNotification;
use App\Services\VehicleStatusNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleStatusNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_notification_when_logistics_status_changes(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-500',
            'vin' => 'VIN500',
            'status' => VehicleStatus::Available,
            'raw_data' => ['status' => 'Loaded'],
        ]);

        $service = app(VehicleStatusNotificationService::class);

        $notification = $service->recordFromRawDataChange(
            $vehicle,
            ['status' => 'Loaded'],
            ['status' => 'At terminal'],
            'sync',
        );

        $this->assertInstanceOf(VehicleStatusNotification::class, $notification);
        $this->assertSame('Loaded', $notification->previous_status);
        $this->assertSame('At terminal', $notification->new_status);
        $this->assertSame('sync', $notification->source);
        $this->assertStringContainsString('At terminal', $service->listUnreadRecent()->first()['preview']);
    }

    public function test_skips_notification_when_status_unchanged(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-501',
            'status' => VehicleStatus::Available,
            'raw_data' => ['status' => 'At terminal'],
        ]);

        $service = app(VehicleStatusNotificationService::class);

        $notification = $service->recordFromRawDataChange(
            $vehicle,
            ['status' => 'At terminal'],
            ['status' => 'At terminal'],
            'sync',
        );

        $this->assertNull($notification);
        $this->assertSame(0, $service->unreadCount());
    }
}
