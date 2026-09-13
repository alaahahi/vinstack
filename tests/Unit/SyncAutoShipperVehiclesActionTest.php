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
                        '_id' => '6a7898907fdd7172c1babab1',
                        'vin' => '3N1AB8CV7SY216850',
                        'manufacturer' => 'NISSAN',
                        'model' => 'SENTRA',
                        'year' => 2025,
                        'invoice_date' => '2026-08-07T00:00:00.000Z',
                        'picked_up_date' => '2026-08-18T00:00:00.000Z',
                        'eta_date' => '2026-09-17T00:00:00.000Z',
                        'delivered_date' => null,
                        'container_id' => 'TRKU4491134',
                        'booking_id' => '10258969',
                        'loading_port' => 'NEWARK, NJ',
                        'destination_port' => 'MERSIN, TUR',
                        'auction_name' => 'Copart',
                        'lot_number' => '61012166',
                        'has_keys' => true,
                        'thumbnail_media_id' => 'media-1',
                    ],
                ],
            ]),
            'https://autoshipper.io/api/sync/media' => Http::response([
                'data' => [
                    [
                        '_id' => 'media-1',
                        'vehicle_id' => '6a7898907fdd7172c1babab1',
                        'url' => 'https://autoshippercdn.com/media/auction-1.jpg',
                        'thumbnail_url' => 'https://autoshippercdn.com/media/auction-1_thumb.webp',
                        'category' => 'auction',
                        'media_type' => 'image',
                    ],
                    [
                        'vehicle_id' => '6a7898907fdd7172c1babab1',
                        'url' => 'https://autoshippercdn.com/media/pickup-1.jpg',
                        'category' => 'pickup',
                    ],
                ],
            ]),
            'https://autoshipper.io/api/sync/vehicleCharges' => Http::response([
                'data' => [
                    [
                        'vehicle_id' => '6a7898907fdd7172c1babab1',
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

        $vehicle = Vehicle::query()->where('autoshipper_id', '6a7898907fdd7172c1babab1')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame(VehicleSource::AutoShipper, $vehicle->source);
        $this->assertSame('3N1AB8CV7SY216850', $vehicle->vin);
        $this->assertSame('NISSAN', $vehicle->make);
        $this->assertSame('SENTRA', $vehicle->model);
        $this->assertSame(2025, $vehicle->year);
        $this->assertSame('2026-09-17', $vehicle->eta);
        $this->assertSame('2026-08-07', $vehicle->raw_data['purchase_date']);
        $this->assertSame('2026-08-18', $vehicle->raw_data['loading_date']);
        $this->assertSame('2026-08-18', $vehicle->raw_data['arrived_terminal_date']);
        $this->assertSame('MERSIN, TUR', $vehicle->raw_data['destination']);
        $this->assertSame('NEWARK, NJ', $vehicle->raw_data['loading_point']);
        $this->assertSame('TRKU4491134', $vehicle->raw_data['container_number']);
        $this->assertSame('10258969', $vehicle->raw_data['booking_number']);
        $this->assertSame('Copart', $vehicle->raw_data['auction']);
        $this->assertSame('61012166', $vehicle->raw_data['lot']);
        $this->assertSame('Yes', $vehicle->raw_data['keys']);
        $this->assertSame('On The Way', $vehicle->raw_data['status']);
        $this->assertContains('https://autoshippercdn.com/media/auction-1.jpg', $vehicle->images);
        $this->assertContains('https://autoshippercdn.com/media/pickup-1.jpg', $vehicle->images);
        $this->assertSame('https://autoshippercdn.com/media/auction-1.jpg', $vehicle->raw_data['thumbnail_url']);
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
