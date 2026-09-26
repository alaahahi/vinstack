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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
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

    public function test_sync_skips_create_when_vin_owned_by_another_source(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.test/api',
            'sync_enabled' => true,
        ]);

        Vehicle::query()->create([
            'source' => VehicleSource::AutoShipper,
            'vinstack_id' => null,
            'autoshipper_id' => 'as-1',
            'vin' => '1HGCM82633A004444',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'status' => VehicleStatus::Available,
            'raw_data' => [],
        ]);

        $this->mock(VinstackService::class, function ($mock): void {
            $mock->shouldReceive('autos')->once()->andReturn([
                [
                    'id' => 'vs-collision-1',
                    'vin' => '1HGCM82633A004444',
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'year' => 2024,
                    'status' => 'At port',
                    'images' => [],
                ],
            ]);
        });

        $this->mock(VehicleStatusNotificationService::class, function ($mock): void {
            $mock->shouldNotReceive('recordFromRawDataChange');
        });

        $this->mock(DealerNotificationService::class, function ($mock): void {
            $mock->shouldNotReceive('notifyVehicleUpdated');
        });

        $result = app(SyncVehiclesAction::class)->execute();

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertDatabaseMissing('vehicles', [
            'vinstack_id' => 'vs-collision-1',
        ]);
        $this->assertSame(1, Vehicle::query()->where('vin', '1HGCM82633A004444')->count());
    }

    public function test_a_locked_row_does_not_abort_the_rest_of_the_batch(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.test/api',
            'sync_enabled' => true,
        ]);

        foreach (['vs-locked', 'vs-ok'] as $id) {
            Vehicle::query()->create([
                'source' => VehicleSource::Vinstack,
                'vinstack_id' => $id,
                'vin' => strtoupper($id === 'vs-locked' ? '1HGCM82633A001111' : '1HGCM82633A002222'),
                'status' => VehicleStatus::Available,
                'raw_data' => ['status' => 'At port'],
            ]);
        }

        // Simulate SQLite refusing the first row's write for the whole retry budget.
        Vehicle::updating(function (Vehicle $vehicle): void {
            if ($vehicle->vinstack_id === 'vs-locked') {
                throw new QueryException(
                    'sqlite',
                    'update "vehicles" set "raw_data" = ? where "id" = ?',
                    [],
                    new PDOException('SQLSTATE[HY000]: General error: 5 database is locked'),
                );
            }
        });

        $this->mock(VinstackService::class, function ($mock): void {
            $mock->shouldReceive('autos')->once()->andReturn([
                ['id' => 'vs-locked', 'vin' => '1HGCM82633A001111', 'status' => 'Shipped', 'images' => []],
                ['id' => 'vs-ok', 'vin' => '1HGCM82633A002222', 'status' => 'Shipped', 'images' => []],
            ]);
        });

        $this->mock(VehicleStatusNotificationService::class, function ($mock): void {
            // Only the row that actually landed gets a status-change check.
            $mock->shouldReceive('recordFromRawDataChange')->once()->andReturn(null);
        });

        $result = app(SyncVehiclesAction::class)->execute();

        $this->assertSame(1, $result['failed']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(
            'Shipped',
            Vehicle::query()->where('vinstack_id', 'vs-ok')->firstOrFail()->raw_data['status'],
        );
        $this->assertSame(
            'At port',
            Vehicle::query()->where('vinstack_id', 'vs-locked')->firstOrFail()->raw_data['status'],
        );
    }

    public function test_re_syncing_an_unchanged_payload_writes_nothing(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.test/api',
            'sync_enabled' => true,
        ]);

        $payload = [
            'id' => 'vs-stable-1',
            'vin' => '1HGCM82633A003333',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2024,
            'status' => 'At port',
            'images' => [],
        ];

        $this->mock(VinstackService::class, function ($mock) use ($payload): void {
            $mock->shouldReceive('autos')->times(3)->andReturn([$payload]);
        });

        $this->mock(VehicleStatusNotificationService::class, function ($mock): void {
            $mock->shouldReceive('recordFromRawDataChange')->andReturn(null);
        });

        // Run 1 creates the row; run 2 settles the keys that mergeSyncPayload
        // adds on top of the create payload (gallery, thumbnail_url). From run 3
        // on, an unchanged upstream payload must produce no write at all.
        app(SyncVehiclesAction::class)->execute();
        app(SyncVehiclesAction::class)->execute();

        $vehicleWrites = [];
        DB::listen(function ($query) use (&$vehicleWrites): void {
            if (str_starts_with($query->sql, 'update "vehicles"')) {
                $vehicleWrites[] = $query->sql;
            }
        });

        app(SyncVehiclesAction::class)->execute();

        // Guards the blob-rewrite regression: an unchanged upstream payload must
        // not touch the row, or every sync holds the SQLite write lock for nothing.
        $this->assertSame([], $vehicleWrites);
        $this->assertSame(1, Vehicle::query()->where('vinstack_id', 'vs-stable-1')->count());
    }
}
