<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Services\VehicleUploadedImageService;
use App\Support\VehicleGalleryMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleGalleryMergerTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_sync_payload_preserves_gallery_api_images_and_adds_sync_images(): void
    {
        $galleryUrl = 'https://cdn.example.com/gallery/extra-1.jpg';
        $syncUrl = 'https://cdn.example.com/list/thumb-1.jpg';

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-100',
            'vin' => 'VIN100',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'price' => 15000,
            'status' => VehicleStatus::Available,
            'images' => [$galleryUrl],
            'raw_data' => [
                'id' => 'vs-100',
                'make' => 'Toyota',
                'images' => [$galleryUrl],
                'images_by_stage' => [
                    'terminal' => [$galleryUrl],
                    'pickup' => [],
                    'destination' => [],
                ],
                'gallery' => [
                    'terminal' => [
                        'urls' => [$galleryUrl],
                        'keys' => [null],
                    ],
                ],
                'gallery_synced_at' => '2026-06-01T12:00:00Z',
                'thumbnail_url' => $galleryUrl,
            ],
        ]);

        $payload = [
            'vin' => 'VIN100',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'price' => 16000,
            'images' => [$syncUrl],
            'raw_data' => [
                'id' => 'vs-100',
                'make' => 'Toyota',
                'model' => 'Camry',
                'price' => 16000,
                'images' => [$syncUrl],
                'images_by_stage' => [
                    'terminal' => [$syncUrl],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ];

        $merged = VehicleGalleryMerger::mergeSyncPayload($vehicle, $payload);

        $this->assertSame(16000.0, (float) $merged['price']);
        $this->assertContains($galleryUrl, $merged['images']);
        $this->assertNotContains($syncUrl, $merged['images']);
        $this->assertSame('2026-06-01T12:00:00Z', $merged['raw_data']['gallery_synced_at']);
        $this->assertCount(1, $merged['raw_data']['images_by_stage']['terminal']);
    }

    public function test_merge_sync_payload_dedupes_list_variants_of_same_gallery_image(): void
    {
        $galleryUrl = 'https://cdn.example.com/autos/abc/terminal-1.jpg';
        $syncVariant = 'https://cdn.example.com/autos/abc/thumbnail-terminal-1.jpg';

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-200',
            'vin' => 'VIN200',
            'status' => VehicleStatus::Available,
            'images' => [$galleryUrl],
            'raw_data' => [
                'images_by_stage' => [
                    'terminal' => [$galleryUrl],
                    'pickup' => [],
                    'destination' => [],
                ],
                'gallery' => [
                    'terminal' => ['urls' => [$galleryUrl], 'keys' => [null]],
                ],
            ],
        ]);

        $merged = VehicleGalleryMerger::mergeSyncPayload($vehicle, [
            'price' => 12000,
            'images' => [$syncVariant],
            'raw_data' => [
                'price' => 12000,
                'images' => [$syncVariant],
                'images_by_stage' => [
                    'terminal' => [$syncVariant],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ]);

        $this->assertSame([$galleryUrl], $merged['images']);
        $this->assertCount(1, $merged['raw_data']['images_by_stage']['terminal']);
    }

    public function test_enrich_list_vehicle_uses_persisted_images_by_stage_instead_of_re_parsing_flat_images(): void
    {
        $service = app(VehicleUploadedImageService::class);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => 'VIN300',
            'status' => VehicleStatus::Available,
            'images' => [
                'https://cdn.example.com/autos/abc/terminal-1.jpg',
                'https://cdn.example.com/autos/abc/thumbnail-terminal-1.jpg',
            ],
            'raw_data' => [
                'gallery_synced_at' => '2026-06-02T10:00:00Z',
                'images_by_stage' => [
                    'terminal' => ['https://cdn.example.com/autos/abc/terminal-1.jpg'],
                    'pickup' => [],
                    'destination' => [],
                ],
                'gallery' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/autos/abc/terminal-1.jpg'],
                        'keys' => [null],
                    ],
                ],
                'images' => [
                    'https://cdn.example.com/autos/abc/terminal-1.jpg',
                    'https://cdn.example.com/autos/abc/thumbnail-terminal-1.jpg',
                ],
            ],
        ]);

        $enriched = $service->enrichListVehicle($vehicle);

        $this->assertCount(1, $enriched['images']);
        $this->assertCount(1, $enriched['images_by_stage']['terminal']);
    }

    public function test_merge_sync_payload_updates_destination_when_gallery_structure_exists(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-400',
            'vin' => '1HGCY2F89RA800433',
            'status' => VehicleStatus::Available,
            'images' => ['https://cdn.example.com/gallery/existing.jpg'],
            'raw_data' => [
                'destination' => 'Dubai',
                'loading_point' => 'Los Angeles',
                'status' => 'Loaded',
                'gallery' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/gallery/existing.jpg'],
                        'keys' => [null],
                    ],
                ],
                'gallery_synced_at' => '2026-06-01T12:00:00Z',
            ],
        ]);

        $merged = VehicleGalleryMerger::mergeSyncPayload($vehicle, [
            'price' => 18000,
            'images' => ['https://cdn.example.com/gallery/existing.jpg'],
            'raw_data' => [
                'destination' => 'Mersin',
                'loading_point' => 'Toronto',
                'status' => 'At terminal',
                'gallery' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/gallery/existing.jpg'],
                        'keys' => [null],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Mersin', $merged['raw_data']['destination']);
        $this->assertSame('Toronto', $merged['raw_data']['loading_point']);
        $this->assertSame('At terminal', $merged['raw_data']['status']);
    }
}
