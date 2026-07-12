<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use ZipArchive;

class ContainerZipUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_container_zip_to_server(): void
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
                ->twice()
                ->andReturn(
                    [
                        'url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                        'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-1.jpg',
                        'public_id' => 'vinstack/containers/MSCU1234567/photo-1',
                    ],
                    [
                        'url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-2.jpg',
                        'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/photo-2.jpg',
                        'public_id' => 'vinstack/containers/MSCU1234567/photo-2',
                    ],
                );
        });

        Sanctum::actingAs($admin);

        $zipPath = $this->makeZipWithImages([
            '1HGBH41JXMN109186.jpg' => UploadedFile::fake()->image('1HGBH41JXMN109186.jpg')->getContent(),
            '2HGBH41JXMN109187.jpg' => UploadedFile::fake()->image('2HGBH41JXMN109187.jpg')->getContent(),
        ]);

        $zip = new UploadedFile($zipPath, 'container.zip', 'application/zip', null, true);

        $response = $this->post('/api/admin/containers/MSCU1234567/images/zip', [
            'replace' => '1',
            'zip' => $zip,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.meta.count', 2)
            ->assertJsonPath('data.uploaded', 2);

        @unlink($zipPath);
    }

    /**
     * @param  array<string, string>  $files
     */
    protected function makeZipWithImages(array $files): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'container-test-zip-').'.zip';
        $archive = new ZipArchive;
        $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $archive->addFromString($name, $contents);
        }

        $archive->close();

        return $zipPath;
    }
}
