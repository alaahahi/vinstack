<?php

namespace Tests\Unit;

use App\Actions\SyncVehiclesAction;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VinstackSetting;
use App\Services\DealerNotificationService;
use App\Services\VehicleStatusNotificationService;
use App\Services\VinstackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncVehiclesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_eta_on_existing_vehicle(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.test/api',
            'sync_enabled' => true,
        ]);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-eta-1',
            'vin' => '1HGCM82633A009991',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2024,
            'eta' => '2026-07-01',
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'status' => 'At port',
                'eta' => '2026-07-01',
            ],
        ]);

        $this->mock(VinstackService::class, function ($mock): void {
            $mock->shouldReceive('autos')->once()->andReturn([
                [
                    'id' => 'vs-eta-1',
                    'vin' => '1HGCM82633A009991',
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'year' => 2024,
                    'eta_date' => '2026-08-18T14:30:00Z',
                    'purchase_date' => '2026-07-05T00:00:00.000Z',
                    'status' => 'Shipped',
                    'images' => [],
                ],
            ]);
        });

        $this->mock(VehicleStatusNotificationService::class, function ($mock): void {
            $mock->shouldReceive('recordFromRawDataChange')->once()->andReturn(null);
        });

        $this->mock(DealerNotificationService::class, function ($mock): void {
            $mock->shouldNotReceive('notifyVehicleUpdated');
        });

        $result = app(SyncVehiclesAction::class)->execute();

        $vehicle = Vehicle::query()->where('vinstack_id', 'vs-eta-1')->firstOrFail();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('2026-08-18', $vehicle->eta);
        $this->assertSame('2026-08-18', $vehicle->raw_data['eta']);
        $this->assertSame('2026-07-05', $vehicle->raw_data['purchase_date']);
        $this->assertSame('Shipped', $vehicle->raw_data['status']);
    }
}
