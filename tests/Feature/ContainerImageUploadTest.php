<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ContainerImage;
use App\Models\User;
use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContainerImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_container_images_via_multipart_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

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
                    'url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                    'public_id' => 'vinstack/containers/MSCU1234567/photo-1',
                ]);
        });

        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('1HGBH41JXMN109186.jpg');

        $response = $this->call(
            'POST',
            '/api/admin/containers/MSCU1234567/images/upload',
            [
                'replace' => '1',
                'metadata' => json_encode([
                    ['name' => '1HGBH41JXMN109186.jpg', 'vin' => '1HGBH41JXMN109186', 'lot' => '123'],
                ]),
            ],
            [],
            [
                'images' => [
                    0 => $file,
                ],
            ],
            $this->transformHeadersToServerVars([
                'Accept' => 'application/json',
            ]),
        );

        $response->assertCreated()
            ->assertJsonPath('data.meta.count', 1)
            ->assertJsonPath('data.byVin.1HGBH41JXMN109186.0', 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg');
    }

    public function test_admin_can_upload_container_images_via_cloudinary_mock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

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
                    'url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                    'public_id' => 'vinstack/containers/MSCU1234567/photo-1',
                ]);
        });

        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('1HGBH41JXMN109186.jpg');

        $response = $this->postJson('/api/admin/containers/MSCU1234567/images/upload', [
            'replace' => true,
            'metadata' => json_encode([
                ['name' => '1HGBH41JXMN109186.jpg', 'vin' => '1HGBH41JXMN109186', 'lot' => '123'],
            ]),
            'images' => [$file],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.meta.count', 1)
            ->assertJsonPath('data.byVin.1HGBH41JXMN109186.0', 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg');

        $this->assertDatabaseHas('container_images', [
            'container_number' => 'MSCU1234567',
            'vin' => '1HGBH41JXMN109186',
            'public_id' => 'vinstack/containers/MSCU1234567/photo-1',
        ]);
    }

    public function test_admin_can_list_container_images(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        ContainerImage::query()->create([
            'container_number' => 'MSCU999',
            'vin' => 'VIN123',
            'original_name' => 'photo.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg',
            'public_id' => 'vinstack/containers/MSCU999/photo-1',
            'uploaded_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/containers/MSCU999/images')
            ->assertOk()
            ->assertJsonPath('data.meta.count', 1)
            ->assertJsonPath('data.images.0.url', 'https://res.cloudinary.com/demo/image/upload/v1/photo.jpg');
    }

    public function test_upload_rejected_when_cloudinary_not_configured(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/admin/containers/MSCU123/images/upload', [
            'images' => [$file],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Cloudinary is not configured. Add credentials in Settings or .env.');
    }

    public function test_admin_can_check_cloudinary_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/containers/cloudinary-status')
            ->assertOk()
            ->assertJsonPath('data.configured', true)
            ->assertJsonPath('data.cloud_name', 'demo');
    }

    public function test_upload_returns_cloudinary_errors_when_all_files_fail(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        VinstackSetting::query()->create([
            'cloudinary_cloud_name' => 'demo',
            'cloudinary_api_key' => '123',
            'cloudinary_api_secret' => 'secret',
        ]);

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
                ->andThrow(new \RuntimeException('Invalid API key'));
        });

        Sanctum::actingAs($admin);

        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/admin/containers/MSCU123/images/upload', [
            'replace' => true,
            'metadata' => json_encode([
                ['name' => 'photo.jpg', 'vin' => null, 'lot' => null],
            ]),
            'images' => [$file],
        ])->assertStatus(422)
            ->assertJsonPath('data.uploaded', 0)
            ->assertJsonPath('failed.0.error', 'Invalid API key')
            ->assertJsonFragment(['message' => '0 images uploaded to Cloudinary. Invalid API key']);
    }
}
