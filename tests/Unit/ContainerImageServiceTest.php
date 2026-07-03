<?php

namespace Tests\Unit;

use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use App\Services\ContainerImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContainerImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_batch_discards_temp_files_after_cloudinary_upload(): void
    {
        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'container-img-');
        $this->assertNotFalse($path);
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        ));

        $file = new UploadedFile($path, 'photo.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);

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
                    'url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
                    'public_id' => 'vinstack/containers/MSCU123/photo-1',
                ]);
        });

        $service = app(ContainerImageService::class);

        $service->uploadBatch(
            'MSCU123',
            [$file],
            [['name' => 'photo.jpg', 'vin' => null, 'lot' => null]],
            true,
        );

        $this->assertFileDoesNotExist($path);
    }

    public function test_upload_batch_discards_temp_files_when_cloudinary_fails(): void
    {
        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'container-img-');
        $this->assertNotFalse($path);
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        ));

        $file = new UploadedFile($path, 'photo.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);

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
                ->andThrow(new \RuntimeException('Upload failed'));
        });

        $service = app(ContainerImageService::class);

        $service->uploadBatch(
            'MSCU123',
            [$file],
            [['name' => 'photo.jpg', 'vin' => null, 'lot' => null]],
            true,
        );

        $this->assertFileDoesNotExist($path);
    }

    public function test_delete_removes_db_record_even_when_cloudinary_destroy_fails(): void
    {
        $image = \App\Models\ContainerImage::query()->create([
            'container_number' => 'MSCU123',
            'original_name' => 'photo.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
            'public_id' => 'vinstack/containers/MSCU123/photo-1',
            'uploaded_at' => now(),
        ]);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('destroy')
                ->once()
                ->andThrow(new \RuntimeException('Cloudinary unavailable'));
        });

        $service = app(ContainerImageService::class);
        $result = $service->delete('MSCU123', $image);

        $this->assertSame(
            'Image removed from container gallery; Cloudinary delete failed.',
            $result['cloudinary_warning'],
        );
        $this->assertDatabaseMissing('container_images', ['id' => $image->id]);
        $this->assertSame(0, $result['payload']['meta']['count']);
    }

    public function test_payload_for_container_resolves_images_via_alternate_refs(): void
    {
        \App\Models\ContainerImage::query()->create([
            'container_number' => 'MSCU1234567',
            'original_name' => 'photo.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
            'public_id' => 'vinstack/containers/MSCU1234567/photo-1',
            'uploaded_at' => now(),
        ]);

        $service = app(ContainerImageService::class);

        $payload = $service->payloadForContainer('BK-ONLY-REF', ['MSCU1234567']);

        $this->assertSame(1, $payload['meta']['count']);
        $this->assertSame('MSCU1234567', $service->normalizeContainerNumber('mscu 1234567'));
    }

    public function test_payload_for_container_keys_tries_each_ref_until_match(): void
    {
        \App\Models\ContainerImage::query()->create([
            'container_number' => 'BKG789',
            'original_name' => 'photo.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
            'public_id' => 'vinstack/containers/BKG789/photo-1',
            'uploaded_at' => now(),
        ]);

        $service = app(ContainerImageService::class);

        $payload = $service->payloadForContainerKeys(['UNKNOWN', 'BKG789']);

        $this->assertSame(1, $payload['meta']['count']);
    }
}
