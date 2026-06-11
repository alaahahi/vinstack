<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\VinstackSetting;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use ZipArchive;

class VehicleZipImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_vehicle_zip_to_vinstack_gallery(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vin = '1HGBH41JXMN109186';

        VinstackSetting::query()->create([
            'gallery_api_base_url' => 'https://app.vinstack.com/api/client-portal',
            'gallery_api_token' => 'gallery-token',
        ]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => $vin,
            'vinstack_id' => '507f1f77bcf86cd799439011',
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'id' => '507f1f77bcf86cd799439011',
            ],
        ]);

        Http::fake([
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery/terminal' => Http::response([
                'data' => ['url' => 'https://cdn.example.com/terminal-1.jpg'],
            ], 201),
            'https://app.vinstack.com/api/client-portal/autos/'.$vin.'/gallery' => Http::response([
                'data' => [
                    'terminal' => ['urls' => ['https://cdn.example.com/terminal-1.jpg']],
                    'pickup' => ['urls' => []],
                    'destination' => ['urls' => []],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($admin);

        $zip = $this->makeZipWithImages([
            'photo-1.jpg' => UploadedFile::fake()->image('photo-1.jpg')->getContent(),
        ]);

        $response = $this->post('/api/admin/vehicles/'.$vehicle->id.'/images/zip', [
            'stage' => 'terminal',
            'zip' => $zip,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.uploaded', 1)
            ->assertJsonPath('data.gallery.images_by_stage.terminal.0', 'https://cdn.example.com/terminal-1.jpg');
    }

    public function test_manual_vehicle_zip_upload_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'MANUALVIN123456789',
            'status' => VehicleStatus::Available,
            'raw_data' => [],
        ]);

        Sanctum::actingAs($admin);

        $zip = $this->makeZipWithImages([
            'photo-1.jpg' => UploadedFile::fake()->image('photo-1.jpg')->getContent(),
        ]);

        $this->post('/api/admin/vehicles/'.$vehicle->id.'/images/zip', [
            'stage' => 'terminal',
            'zip' => $zip,
        ], [
            'Accept' => 'application/json',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط.');
    }

    /**
     * @param  array<string, string>  $files
     */
    protected function makeZipWithImages(array $files): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'vehicle-zip-');
        $zipPath = $temp.'.zip';
        @unlink($temp);

        $archive = new ZipArchive;
        $archive->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $archive->addFromString($name, $contents);
        }

        $archive->close();

        return new UploadedFile($zipPath, 'photos.zip', 'application/zip', null, true);
    }
}
