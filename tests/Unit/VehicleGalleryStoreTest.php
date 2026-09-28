<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleGalleryStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VehicleGalleryStoreTest extends TestCase
{
    use RefreshDatabase;

    private string $galleryDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->galleryDir = storage_path('framework/testing/vehicle-galleries-'.uniqid());
        config([
            'vehicle_gallery.file_store_enabled' => true,
            'vehicle_gallery.path' => $this->galleryDir,
        ]);
        File::ensureDirectoryExists($this->galleryDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->galleryDir)) {
            File::deleteDirectory($this->galleryDir);
        }

        parent::tearDown();
    }

    public function test_append_writes_file_not_database(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'GALLERYSTOREVIN0001',
            'status' => VehicleStatus::Available,
        ]);

        $row = VehicleGalleryStore::append($vehicle, [
            'stage' => 'terminal',
            'url' => 'https://res.cloudinary.com/demo/a.jpg',
            'public_id' => 'demo/a',
            'original_name' => 'a.jpg',
            'uploaded_by' => User::factory()->create()->id,
        ]);

        $this->assertTrue(VehicleGalleryStore::isFileGalleryId($row['id']));
        $this->assertFileExists(VehicleGalleryStore::pathFor($vehicle));
        $this->assertSame(0, VehicleUploadedImage::query()->count());
        $this->assertCount(1, VehicleGalleryStore::images($vehicle));
    }

    public function test_merger_dual_reads_legacy_db_and_file_gallery(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'GALLERYSTOREVIN0002',
            'status' => VehicleStatus::Available,
            'raw_data' => [],
        ]);

        VehicleUploadedImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'stage' => 'pickup',
            'path' => null,
            'cloudinary_url' => 'https://res.cloudinary.com/demo/legacy.jpg',
            'public_id' => 'demo/legacy',
            'original_name' => 'legacy.jpg',
            'uploaded_by' => $user->id,
        ]);

        VehicleGalleryStore::append($vehicle, [
            'stage' => 'terminal',
            'url' => 'https://res.cloudinary.com/demo/new.jpg',
            'public_id' => 'demo/new',
            'original_name' => 'new.jpg',
            'uploaded_by' => $user->id,
        ]);

        $vehicle->refresh()->load('uploadedImages');
        $stages = VehicleGalleryMerger::resolveDisplayStages([], $vehicle);

        $this->assertContains('https://res.cloudinary.com/demo/legacy.jpg', $stages['pickup']);
        $this->assertContains('https://res.cloudinary.com/demo/new.jpg', $stages['terminal']);
    }

    public function test_remove_deletes_file_entry(): void
    {
        $vehicle = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vin' => 'GALLERYSTOREVIN0003',
            'status' => VehicleStatus::Available,
        ]);

        $row = VehicleGalleryStore::append($vehicle, [
            'stage' => 'destination',
            'url' => 'https://res.cloudinary.com/demo/b.jpg',
            'public_id' => 'demo/b',
            'original_name' => 'b.jpg',
            'uploaded_by' => 1,
        ]);

        $removed = VehicleGalleryStore::remove($vehicle, $row['id']);

        $this->assertNotNull($removed);
        $this->assertSame([], VehicleGalleryStore::images($vehicle));
        $this->assertFileDoesNotExist(VehicleGalleryStore::pathFor($vehicle));
    }
}
