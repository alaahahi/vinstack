<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Services\VehicleVinstackZipUploadService;
use App\Services\VinstackGalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class VehicleVinstackZipUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_vehicle_with_identifier_can_upload_zip(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => '1HGBH41JXMN109186',
            'status' => VehicleStatus::Available,
            'raw_data' => [],
        ]);

        $gallery = $this->createMock(VinstackGalleryService::class);
        $gallery->expects($this->once())
            ->method('resolveGalleryIdentifiers')
            ->with($vehicle)
            ->willReturn(['1HGBH41JXMN109186']);
        $gallery->expects($this->once())
            ->method('uploadStageImage')
            ->with(
                $vehicle,
                'terminal',
                $this->isType('string'),
                'terminal.jpg',
            )
            ->willReturn([
                'url' => 'https://cdn.example.com/terminal.jpg',
                'response' => [],
            ]);
        $gallery->expects($this->once())
            ->method('buildGalleryPayload')
            ->willReturn([
                'images_by_stage' => [
                    'terminal' => ['https://cdn.example.com/terminal.jpg'],
                    'pickup' => [],
                    'destination' => [],
                ],
            ]);

        $service = new VehicleVinstackZipUploadService($gallery);
        $zip = $this->makeZipWithFiles([
            'terminal.jpg' => base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                true,
            ),
        ]);

        $result = $service->uploadZip($vehicle, 'terminal', $zip);

        $this->assertSame(1, $result['uploaded']);
        $this->assertSame([], $result['failed']);
    }

    public function test_extract_rejects_zip_without_images(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vin' => '1HGBH41JXMN109186',
            'status' => VehicleStatus::Available,
            'raw_data' => [],
        ]);

        $gallery = $this->createMock(VinstackGalleryService::class);
        $gallery->method('resolveGalleryIdentifiers')->willReturn(['1HGBH41JXMN109186']);

        $service = new VehicleVinstackZipUploadService($gallery);
        $zip = $this->makeZipWithFiles([
            'notes.txt' => 'not an image',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('zip_no_images');

        $service->uploadZip($vehicle, 'terminal', $zip);
    }

    /**
     * @param  array<string, string>  $files
     */
    protected function makeZipWithFiles(array $files): UploadedFile
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
