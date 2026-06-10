<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\VinstackSetting;
use App\Models\Vehicle;
use App\Services\VinstackGalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VinstackGalleryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function galleryService(): VinstackGalleryService
    {
        return new VinstackGalleryService(
            uploadedImages: $this->createMock(\App\Services\VehicleUploadedImageService::class),
        );
    }

    public function test_live_gallery_api_applies_only_to_vinstack_vehicles(): void
    {
        $service = $this->galleryService();

        $vinstack = new Vehicle(['source' => VehicleSource::Vinstack]);
        $manual = new Vehicle(['source' => VehicleSource::Manual]);

        $this->assertTrue($service->usesLiveGalleryApi($vinstack));
        $this->assertFalse($service->usesLiveGalleryApi($manual));
    }

    public function test_resolve_gallery_identifiers_prefers_vin_then_vinstack_id(): void
    {
        $service = $this->galleryService();

        $vehicle = new Vehicle([
            'vin' => '1HGBH41JXMN109186',
            'vinstack_id' => '507f1f77bcf86cd799439011',
            'raw_data' => [
                'id' => '507f1f77bcf86cd799439011',
            ],
        ]);

        $this->assertSame(
            ['1HGBH41JXMN109186', '507f1f77bcf86cd799439011'],
            $service->resolveGalleryIdentifiers($vehicle),
        );
    }

    public function test_fetch_gallery_for_vehicle_falls_back_to_vinstack_id_when_vin_rejected(): void
    {
        $vin = '1HGBH41JXMN109186';
        $vinstackId = '507f1f77bcf86cd799439011';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'error' => 'Invalid vehicle id',
            ], 400),
            'https://app.vinstack.com/api/client-portal/autos/'.$vinstackId.'/gallery' => Http::response([
                'data' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/terminal.jpg'],
                    ],
                    'pickup' => [
                        'urls' => [],
                    ],
                    'destination' => [
                        'urls' => [],
                    ],
                ],
            ], 200),
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => $vinstackId,
            'vin' => $vin,
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'id' => $vinstackId,
            ],
        ]);

        $service = $this->galleryService();
        $payload = $service->fetchGalleryForVehicle($vehicle);

        $this->assertSame(
            ['https://cdn.example.com/terminal.jpg'],
            $payload['images_by_stage']['terminal'] ?? [],
        );

        Http::assertSentCount(2);
    }

    public function test_build_gallery_payload_sets_gallery_fresh_when_api_succeeds(): void
    {
        $vin = '1HGBH41JXMN109186';
        $vinstackId = '507f1f77bcf86cd799439011';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'error' => 'Invalid vehicle id',
            ], 400),
            'https://app.vinstack.com/api/client-portal/autos/'.$vinstackId.'/gallery' => Http::response([
                'data' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/terminal.jpg'],
                    ],
                    'pickup' => [
                        'urls' => ['https://cdn.example.com/pickup.jpg'],
                    ],
                    'destination' => [
                        'urls' => [],
                    ],
                ],
            ], 200),
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => $vinstackId,
            'vin' => $vin,
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'id' => $vinstackId,
            ],
        ]);

        $payload = $this->galleryService()->buildGalleryPayload($vehicle);

        $this->assertTrue($payload['gallery_fresh']);
        $this->assertNull($payload['gallery_error']);
        $this->assertCount(1, $payload['images_by_stage']['terminal']);
        $this->assertCount(1, $payload['images_by_stage']['pickup']);
    }

    public function test_stages_changed_detects_replaced_urls_with_same_count(): void
    {
        $service = $this->galleryService();

        $method = new \ReflectionMethod(VinstackGalleryService::class, 'stagesChanged');
        $method->setAccessible(true);

        $before = [
            'terminal' => ['https://cdn.example/old-1.jpg'],
            'pickup' => [],
            'destination' => [],
        ];
        $after = [
            'terminal' => ['https://cdn.example/new-1.jpg'],
            'pickup' => [],
            'destination' => [],
        ];

        $this->assertTrue($method->invoke($service, $before, $after));
    }

    public function test_build_gallery_payload_persists_new_images_to_raw_data(): void
    {
        $vin = '1HGBH41JXMN109186';
        $vinstackId = '507f1f77bcf86cd799439011';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'data' => [
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/terminal-1.jpg', 'https://cdn.example.com/terminal-2.jpg'],
                    ],
                    'pickup' => [
                        'urls' => ['https://cdn.example.com/pickup.jpg'],
                    ],
                    'destination' => [
                        'urls' => [],
                    ],
                ],
            ], 200),
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => $vinstackId,
            'vin' => $vin,
            'status' => VehicleStatus::Available,
            'images' => ['https://cdn.example.com/terminal-1.jpg'],
            'raw_data' => [
                'id' => $vinstackId,
                'images_by_stage' => [
                    'terminal' => ['https://cdn.example.com/terminal-1.jpg'],
                    'pickup' => [],
                    'destination' => [],
                ],
            ],
        ]);

        $payload = $this->galleryService()->buildGalleryPayload($vehicle->fresh());

        $this->assertTrue($payload['gallery_fresh']);
        $this->assertTrue($payload['gallery_stored']);
        $this->assertSame(2, $payload['gallery_new_images_count']);
        $this->assertCount(2, $payload['images_by_stage']['terminal']);
        $this->assertCount(1, $payload['images_by_stage']['pickup']);

        $vehicle->refresh();

        $this->assertCount(3, $vehicle->images);
        $this->assertSame(
            ['https://cdn.example.com/terminal-1.jpg', 'https://cdn.example.com/terminal-2.jpg'],
            $vehicle->raw_data['images_by_stage']['terminal'] ?? [],
        );
        $this->assertSame(
            ['https://cdn.example.com/pickup.jpg'],
            $vehicle->raw_data['images_by_stage']['pickup'] ?? [],
        );
        $this->assertArrayHasKey('gallery_synced_at', $vehicle->raw_data);
    }

    public function test_persist_gallery_images_preserves_list_metadata_in_raw_data(): void
    {
        $vin = '1HGBH41JXMN109186';
        $vinstackId = '507f1f77bcf86cd799439011';
        $originalCreatedAt = '2024-06-01T10:00:00+00:00';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'data' => [
                    'id' => $vinstackId,
                    'created_at' => '2019-01-01T00:00:00+00:00',
                    'status' => 'Delivered',
                    'source' => 'client_portal',
                    'terminal' => [
                        'urls' => ['https://cdn.example.com/terminal-new.jpg'],
                    ],
                    'pickup' => [
                        'urls' => [],
                    ],
                    'destination' => [
                        'urls' => [],
                    ],
                ],
            ], 200),
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => $vinstackId,
            'vin' => $vin,
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'id' => $vinstackId,
                'created_at' => $originalCreatedAt,
                'status' => 'In Transit',
                'source' => VehicleSource::Vinstack->value,
                'lot' => 'A-100',
            ],
        ]);

        $this->galleryService()->buildGalleryPayload($vehicle->fresh());

        $vehicle->refresh();

        $this->assertSame($originalCreatedAt, $vehicle->raw_data['created_at'] ?? null);
        $this->assertSame('In Transit', $vehicle->raw_data['status'] ?? null);
        $this->assertSame(VehicleSource::Vinstack->value, $vehicle->raw_data['source'] ?? null);
        $this->assertSame('A-100', $vehicle->raw_data['lot'] ?? null);
        $this->assertSame(VehicleSource::Vinstack, $vehicle->source);
        $this->assertSame(VehicleStatus::Available, $vehicle->status);
    }
}
