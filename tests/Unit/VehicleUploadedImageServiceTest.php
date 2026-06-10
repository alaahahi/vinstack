<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Services\VehicleUploadedImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleUploadedImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrich_list_vehicle_sets_thumbnail_and_stage_counts_from_client_portal_blocks(): void
    {
        $service = new VehicleUploadedImageService;

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => '1HGBH41JXMN109186',
            'status' => VehicleStatus::Available,
            'images' => ['https://cdn.example.com/autos/abc/stale-only.jpg'],
            'raw_data' => [
                'thumbnail_url' => 'https://cdn.example.com/autos/abc/thumbnail-preview.jpg',
                'terminal' => [
                    'urls' => [
                        'https://cdn.example.com/autos/abc/terminal-1.jpg',
                        'https://cdn.example.com/autos/abc/terminal-2.jpg',
                    ],
                ],
                'pickup' => [
                    'urls' => [
                        'https://cdn.example.com/autos/abc/pickup-1.jpg',
                    ],
                ],
                'destination' => [
                    'urls' => array_fill(0, 7, 'https://cdn.example.com/autos/abc/destination.jpg'),
                ],
            ],
        ]);

        $enriched = $service->enrichListVehicle($vehicle);

        $this->assertSame('https://cdn.example.com/autos/abc/thumbnail-preview.jpg', $enriched['thumbnail_url']);
        $this->assertCount(2, $enriched['images_by_stage']['terminal']);
        $this->assertCount(1, $enriched['images_by_stage']['pickup']);
        $this->assertCount(1, $enriched['images_by_stage']['destination']);
        $this->assertCount(4, $enriched['images']);
    }

    public function test_enrich_list_vehicle_falls_back_thumbnail_to_first_gallery_image(): void
    {
        $service = new VehicleUploadedImageService;

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => '2HGFG12877H541783',
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'terminal' => [
                    'urls' => ['https://cdn.example.com/autos/xyz/terminal.jpg'],
                ],
                'pickup' => ['urls' => []],
                'destination' => ['urls' => []],
            ],
        ]);

        $enriched = $service->enrichListVehicle($vehicle);

        $this->assertSame('https://cdn.example.com/autos/xyz/terminal.jpg', $enriched['thumbnail_url']);
    }
}
