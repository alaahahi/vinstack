<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Services\ContainerService;
use App\Services\NujoomAlJazeeraImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class NujoomAlJazeeraImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_parse_lot_and_vin_extracts_values(): void
    {
        $service = app(NujoomAlJazeeraImportService::class);

        $parsed = $service->parseLotAndVin('Lot# 44622890Vin# 3N8AP6DC5SL408575');

        $this->assertSame('44622890', $parsed['lot']);
        $this->assertSame('3N8AP6DC5SL408575', $parsed['vin']);
    }

    public function test_parse_year_make_model_from_auction_photo(): void
    {
        $service = app(NujoomAlJazeeraImportService::class);

        $parsed = $service->parseYearMakeModel('NISSAN KICKS SR FWD 2025');

        $this->assertSame('Nissan', $parsed['make']);
        $this->assertSame('Kicks Sr Fwd', $parsed['model']);
        $this->assertSame(2025, $parsed['year']);
    }

    public function test_map_row_builds_vehicle_payload(): void
    {
        $service = app(NujoomAlJazeeraImportService::class);

        $mapped = $service->mapRow([
            'auction_photo' => 'HONDA ACCORD HYBRID SPORT 2025',
            'lot_and_vin' => 'Lot# 44750097Vin# 1HGCY2F54SA066635',
            'auction' => "Boston | IAA\nBuyer Number: AHMAD ABDULKHALIQ 445902",
            'region' => 'New Jersey',
            'destination' => 'MERSIN, TURKEY (TRMER)',
            'purchase_date' => '2026-05-29',
            'auction_price' => '10660',
            'loading_point' => 'NEW YORK, NY (USNYC)',
            'loaded_date' => '2026-06-08',
            'booking_number' => 'NYC060398600',
            'container_number' => 'ARKU8556770',
            'tracking' => 'Loading',
        ]);

        $this->assertSame('1HGCY2F54SA066635', $mapped['vin']);
        $this->assertSame('Honda', $mapped['make']);
        $this->assertSame(2025, $mapped['year']);
        $this->assertSame('ARKU8556770', $mapped['container_number']);
        $this->assertSame('NYC060398600', $mapped['booking_number']);
        $this->assertSame('MERSIN, TURKEY (TRMER)', $mapped['destination']);
        $this->assertSame(10660.0, $mapped['price']);
        $this->assertSame('Loading', $mapped['raw_data']['status']);
        $this->assertSame('AHMAD ABDULKHALIQ 445902', $mapped['raw_data']['buyer']);
    }

    public function test_build_preview_splits_add_and_update_by_vin(): void
    {
        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-existing',
            'vin' => '1HGCY2F54SA066635',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2024,
            'status' => VehicleStatus::Available,
        ]);

        $this->mock(ContainerService::class, function ($mock) {
            $mock->shouldReceive('listForAdmin')->andReturn([]);
        });

        $service = app(NujoomAlJazeeraImportService::class);

        $preview = $service->buildPreview([
            [
                '_excel_row' => 2,
                'auction_photo' => 'HONDA ACCORD HYBRID SPORT 2025',
                'lot_and_vin' => 'Lot# 44750097Vin# 1HGCY2F54SA066635',
                'auction' => 'Boston | IAA',
                'destination' => 'MERSIN, TURKEY (TRMER)',
            ],
            [
                '_excel_row' => 3,
                'auction_photo' => 'TOYOTA CAMRY SE 2025',
                'lot_and_vin' => 'Lot# 44711908Vin# 4T1DAACK6SU551977',
                'auction' => 'Miami | IAA',
                'destination' => 'MERSIN, TURKEY (TRMER)',
            ],
        ]);

        $this->assertCount(1, $preview['to_update']);
        $this->assertCount(1, $preview['to_add']);
        $this->assertSame('1HGCY2F54SA066635', $preview['to_update'][0]['vin']);
        $this->assertSame('4T1DAACK6SU551977', $preview['to_add'][0]['vin']);
    }

    public function test_build_preview_detects_new_containers(): void
    {
        $this->mock(ContainerService::class, function ($mock) {
            $mock->shouldReceive('listForAdmin')->andReturn([
                ['container_number' => 'EXISTING1234567'],
            ]);
        });

        $service = app(NujoomAlJazeeraImportService::class);

        $preview = $service->buildPreview([
            [
                '_excel_row' => 2,
                'auction_photo' => 'HONDA ACCORD HYBRID SPORT 2025',
                'lot_and_vin' => 'Lot# 1Vin# 1HGCY2F54SA066635',
                'auction' => 'Boston | IAA',
                'destination' => 'MERSIN, TURKEY (TRMER)',
                'container_number' => 'ARKU8556770',
                'booking_number' => 'NYC060398600',
                'loading_point' => 'NEW YORK, NY (USNYC)',
            ],
        ]);

        $this->assertCount(1, $preview['containers_new']);
        $this->assertSame('ARKU8556770', $preview['containers_new'][0]['container_number']);
    }

    public function test_apply_creates_and_updates_vehicles_from_cached_preview(): void
    {
        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-existing',
            'vin' => '1HGCY2F54SA066635',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2024,
            'status' => VehicleStatus::Available,
            'raw_data' => ['status' => 'Old'],
        ]);

        $this->mock(ContainerService::class, function ($mock) {
            $mock->shouldReceive('listForAdmin')->andReturn([]);
        });

        $service = app(NujoomAlJazeeraImportService::class);
        $preview = $service->buildPreview([
            [
                '_excel_row' => 2,
                'auction_photo' => 'HONDA ACCORD HYBRID SPORT 2025',
                'lot_and_vin' => 'Lot# 44750097Vin# 1HGCY2F54SA066635',
                'auction' => 'Boston | IAA',
                'destination' => 'MERSIN, TURKEY (TRMER)',
                'tracking' => 'Loading',
            ],
            [
                '_excel_row' => 3,
                'auction_photo' => 'TOYOTA CAMRY SE 2025',
                'lot_and_vin' => 'Lot# 44711908Vin# 4T1DAACK6SU551977',
                'auction' => 'Miami | IAA',
                'destination' => 'MERSIN, TURKEY (TRMER)',
            ],
        ]);

        $token = '11111111-1111-1111-1111-111111111111';
        Cache::put('nujoom_import_preview:'.$token, $preview, 600);

        $result = $service->apply($token);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);

        $updated = Vehicle::query()->where('vin', '1HGCY2F54SA066635')->first();
        $created = Vehicle::query()->where('vin', '4T1DAACK6SU551977')->first();

        $this->assertSame(VehicleSource::Vinstack, $updated->source);
        $this->assertSame('Loading', $updated->raw_data['status']);
        $this->assertSame(VehicleSource::NujoomAlJazeera, $created->source);
        $this->assertNull(Cache::get('nujoom_import_preview:'.$token));
    }
}
