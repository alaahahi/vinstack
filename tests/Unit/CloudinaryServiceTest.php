<?php

namespace Tests\Unit;

use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_configured_requires_cloud_name_key_and_secret_or_preset(): void
    {
        config([
            'services.cloudinary.cloud_name' => null,
            'services.cloudinary.api_key' => null,
            'services.cloudinary.api_secret' => null,
            'services.cloudinary.upload_preset' => null,
        ]);

        $service = new CloudinaryService;

        $this->assertFalse($service->isConfigured());

        VinstackSetting::current()->update([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        $this->assertTrue((new CloudinaryService)->isConfigured());
    }

    public function test_is_configured_accepts_upload_preset_without_secret(): void
    {
        VinstackSetting::current()->update([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_upload_preset' => 'unsigned',
        ]);

        $service = new CloudinaryService;

        $this->assertTrue($service->isConfigured());
    }

    public function test_probe_reports_missing_credentials(): void
    {
        $result = (new CloudinaryService)->probe();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('incomplete', strtolower($result['message']));
    }

    public function test_upload_delegates_to_upload_api(): void
    {
        VinstackSetting::current()->update([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        $uploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);
        $uploadApi->shouldReceive('upload')
            ->once()
            ->andReturn(new \Cloudinary\Api\ApiResponse([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/test.jpg',
                'public_id' => 'vinstack/containers/ABCD123/test-1',
            ], []));

        $path = tempnam(sys_get_temp_dir(), 'cloudinary-test');
        file_put_contents($path, 'fake-image');

        $service = new CloudinaryService($uploadApi);
        $result = $service->upload($path, ['folder' => 'vinstack/containers/ABCD123']);

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/v1/test.jpg',
            $result['url'],
        );
        $this->assertSame('vinstack/containers/ABCD123/test-1', $result['public_id']);

        @unlink($path);
    }
}
