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
}
