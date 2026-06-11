<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use App\Services\VehicleUploadedImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleUploadedImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_one_uploads_to_cloudinary_and_discards_temp_file(): void
    {
        Storage::fake('public');

        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'vehicle-img-');
        $this->assertNotFalse($path);
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        ));

        $file = new UploadedFile($path, 'terminal.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('resolveConfig')->andReturn([
                'cloud_name' => 'demo',
                'api_key' => '123',
                'api_secret' => 'secret',
                'upload_preset' => null,
                'folder' => 'vinstack/containers',
            ]);
            $mock->shouldReceive('upload')
                ->once()
                ->andReturn([
                    'url' => 'https://res.cloudinary.com/demo/image/upload/v1/terminal.jpg',
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/terminal.jpg',
                    'public_id' => 'vinstack/containers/vehicles/1/terminal/uuid',
                ]);
        });

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => '1HGBH41JXMN109186',
            'status' => VehicleStatus::Available,
        ]);

        $user = User::factory()->create();

        $service = app(VehicleUploadedImageService::class);
        $result = $service->storeOne($vehicle, 'terminal', $file, $user);

        $this->assertFileDoesNotExist($path);
        $this->assertSame('cloudinary', $result['source']);
        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/v1/terminal.jpg',
            $result['url'],
        );

        $record = VehicleUploadedImage::query()->first();
        $this->assertNotNull($record);
        $this->assertNull($record->path);
        $this->assertSame($result['url'], $record->cloudinary_url);
        Storage::disk('public')->assertMissing('vehicle-images/'.$vehicle->id);
    }

    public function test_local_uploaded_image_public_url_still_serves_storage_path(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'LOCALVIN123456789',
            'status' => VehicleStatus::Available,
        ]);

        $user = User::factory()->create();

        $image = VehicleUploadedImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'stage' => 'terminal',
            'path' => 'vehicle-images/'.$vehicle->id.'/legacy.jpg',
            'original_name' => 'legacy.jpg',
            'uploaded_by' => $user->id,
        ]);

        $this->assertFalse($image->isCloudinary());
        $this->assertSame('/storage/vehicle-images/'.$vehicle->id.'/legacy.jpg', $image->publicUrl());

        $service = app(VehicleUploadedImageService::class);
        $formatted = $service->formatImage($image);

        $this->assertSame('local', $formatted['source']);
        $this->assertSame('/storage/vehicle-images/'.$vehicle->id.'/legacy.jpg', $formatted['url']);
    }

    public function test_enrich_list_vehicle_sets_thumbnail_and_stage_counts_from_client_portal_blocks(): void
    {
        $service = app(VehicleUploadedImageService::class);

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
        $service = app(VehicleUploadedImageService::class);

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

    public function test_enrich_list_vehicle_includes_nujoom_source_label(): void
    {
        $service = app(VehicleUploadedImageService::class);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::NujoomAlJazeera,
            'vin' => '1HGCM82633A004361',
            'status' => VehicleStatus::Available,
        ]);

        $enriched = $service->enrichListVehicle($vehicle);

        $this->assertSame('nujoom_al_jazeera', $enriched['source']);
        $this->assertSame('نجوم الجزيرة', $enriched['source_label']);
    }

    public function test_enrich_list_vehicle_sanitizes_corrupted_destination_for_list_display(): void
    {
        $service = app(VehicleUploadedImageService::class);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => '3VWFE21C04M000001',
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => [
                'loading_point' => 'Toronto',
                'destination' => 'images-1777574841795-37632154.jpeg.images-1777574841796-877959842.jpeg',
                'pod' => 'Mersin',
                'terminal' => [
                    'urls' => ['https://cdn.example.com/autos/abc/terminal.jpg'],
                ],
            ],
        ]);

        $enriched = $service->enrichListVehicle($vehicle);

        $this->assertSame('Mersin', $enriched['raw_data']['destination'] ?? null);
        $this->assertSame('Toronto', $enriched['raw_data']['loading_point'] ?? null);
    }

    public function test_delete_removes_db_record_even_when_cloudinary_destroy_fails(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'DELETECLOUD1234567',
            'status' => VehicleStatus::Available,
        ]);

        $user = User::factory()->create();

        $image = VehicleUploadedImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'stage' => 'terminal',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/image/upload/v1/terminal.jpg',
            'public_id' => 'vinstack/vehicles/1/terminal/uuid',
            'original_name' => 'terminal.jpg',
            'uploaded_by' => $user->id,
        ]);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('destroy')
                ->once()
                ->andThrow(new \RuntimeException('Cloudinary unavailable'));
        });

        $service = app(VehicleUploadedImageService::class);
        $result = $service->delete($vehicle, $image);

        $this->assertSame(
            'Image removed from gallery; Cloudinary delete failed.',
            $result['cloudinary_warning'],
        );
        $this->assertDatabaseMissing('vehicle_uploaded_images', ['id' => $image->id]);
    }
}
