<?php

namespace Tests\Unit;

use App\Actions\SyncAutoShipperVehiclesAction;
use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\AutoshipperSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AutoShipperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncAutoShipperVehiclesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_vehicle_with_media_gallery_and_charges(): void
    {
        AutoshipperSetting::current()->update([
            'api_base_url' => 'https://autoshipper.io/api',
            'sync_enabled' => true,
        ]);

        Http::fake([
            'https://autoshipper.io/api/sync/vehicles' => Http::response([
                'data' => [
                    [
                        'id' => 'AS-1001',
                        'vin' => '1HGCM82633A123456',
                        'make' => 'Honda',
                        'model' => 'Accord',
                        'year' => 2021,
                        'price' => 8500,
                        'eta' => '2026-09-20',
                        'status' => 'Loaded',
                        'containerNumber' => 'MSCU1234567',
                        'bookingNumber' => 'BK-99',
                        'destination' => 'Iraq',
                    ],
                ],
            ]),
            'https://autoshipper.io/api/sync/media' => Http::response([
                'data' => [
                    [
                        'vehicleId' => 'AS-1001',
                        'url' => 'https://cdn.example.com/terminal/car1.jpg',
                        'type' => 'terminal',
                    ],
                    [
                        'vehicle_id' => 'AS-1001',
                        'url' => 'https://cdn.example.com/pickup/car1.jpg',
                        'stage' => 'pickup',
                    ],
                ],
            ]),
            'https://autoshipper.io/api/sync/vehicleCharges' => Http::response([
                'data' => [
                    [
                        'vehicleId' => 'AS-1001',
                        'name' => 'Ocean Freight',
                        'amount' => 1200,
                    ],
                ],
            ]),
        ]);

        $result = app(SyncAutoShipperVehiclesAction::class)->execute();

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['total']);

        $vehicle = Vehicle::query()->where('autoshipper_id', 'AS-1001')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame(VehicleSource::AutoShipper, $vehicle->source);
        $this->assertSame('1HGCM82633A123456', $vehicle->vin);
        $this->assertSame('Honda', $vehicle->make);
        $this->assertSame('Accord', $vehicle->model);
        $this->assertSame(2021, $vehicle->year);
        $this->assertSame('2026-09-20', $vehicle->eta);
        $this->assertContains('https://cdn.example.com/terminal/car1.jpg', $vehicle->images);
        $this->assertContains('https://cdn.example.com/pickup/car1.jpg', $vehicle->images);
        $this->assertSame('https://cdn.example.com/terminal/car1.jpg', $vehicle->raw_data['images_by_stage']['terminal'][0] ?? null);
        $this->assertSame('https://cdn.example.com/pickup/car1.jpg', $vehicle->raw_data['images_by_stage']['pickup'][0] ?? null);
        $this->assertSame('Loaded', $vehicle->raw_data['status']);
        $this->assertCount(1, $vehicle->raw_data['vehicle_charges']);
        $this->assertSame('Ocean Freight', $vehicle->raw_data['vehicle_charges'][0]['name']);
    }

    public function test_sync_updates_existing_autoshipper_vehicle(): void
    {
        AutoshipperSetting::current();

        Vehicle::query()->create([
            'source' => VehicleSource::AutoShipper,
            'autoshipper_id' => 'AS-1001',
            'vinstack_id' => null,
            'vin' => '1HGCM82633A123456',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2021,
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => ['status' => 'Purchased'],
        ]);

        Http::fake([
            '*/sync/vehicles' => Http::response([
                'data' => [[
                    'id' => 'AS-1001',
                    'vin' => '1HGCM82633A123456',
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'year' => 2021,
                    'status' => 'Shipped',
                ]],
            ]),
            '*/sync/media' => Http::response(['data' => []]),
            '*/sync/vehicleCharges' => Http::response(['data' => []]),
        ]);

        $result = app(SyncAutoShipperVehiclesAction::class)->execute();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);

        $vehicle = Vehicle::query()->where('autoshipper_id', 'AS-1001')->first();
        $this->assertSame('Shipped', $vehicle->raw_data['status']);
    }

    public function test_sync_does_not_overwrite_vinstack_vehicle_by_vin(): void
    {
        AutoshipperSetting::current();

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'VIN-1',
            'vin' => '1HGCM82633A123456',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2021,
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [],
        ]);

        Http::fake([
            '*/sync/vehicles' => Http::response([
                'data' => [[
                    'id' => 'AS-2002',
                    'vin' => '1HGCM82633A123456',
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'year' => 2021,
                ]],
            ]),
            '*/sync/media' => Http::response(['data' => []]),
            '*/sync/vehicleCharges' => Http::response(['data' => []]),
        ]);

        $result = app(SyncAutoShipperVehiclesAction::class)->execute();

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, Vehicle::query()->count());
        $this->assertSame(VehicleSource::Vinstack, Vehicle::query()->first()->source);
    }

    public function test_client_sends_bearer_token_when_configured(): void
    {
        AutoshipperSetting::current()->update([
            'api_base_url' => 'https://autoshipper.io/api',
            'api_token' => 'secret-token',
        ]);

        Http::fake([
            'https://autoshipper.io/api/sync/vehicles' => Http::response(['data' => []]),
        ]);

        app(AutoShipperService::class)->vehicles();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://autoshipper.io/api/sync/vehicles'
                && $request->hasHeader('Authorization', 'Bearer secret-token');
        });
    }

    public function test_client_does_not_use_env_token_fallback(): void
    {
        AutoshipperSetting::current()->update([
            'api_base_url' => 'https://autoshipper.io/api',
            'api_token' => null,
        ]);

        config(['services.autoshipper.token' => 'env-should-be-ignored']);

        Http::fake([
            'https://autoshipper.io/api/sync/vehicles' => Http::response(['data' => []]),
        ]);

        app(AutoShipperService::class)->vehicles();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://autoshipper.io/api/sync/vehicles'
                && ! $request->hasHeader('Authorization');
        });
    }
}
