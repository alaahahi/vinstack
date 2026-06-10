<?php

namespace Tests\Unit;

use App\Enums\VehicleSource;
use App\Models\Vehicle;
use App\Services\VinstackGalleryService;
use PHPUnit\Framework\TestCase;

class VinstackGalleryServiceTest extends TestCase
{
    public function test_live_gallery_api_applies_only_to_vinstack_vehicles(): void
    {
        $service = new VinstackGalleryService(
            uploadedImages: $this->createMock(\App\Services\VehicleUploadedImageService::class),
        );

        $vinstack = new Vehicle(['source' => VehicleSource::Vinstack]);
        $manual = new Vehicle(['source' => VehicleSource::Manual]);

        $this->assertTrue($service->usesLiveGalleryApi($vinstack));
        $this->assertFalse($service->usesLiveGalleryApi($manual));
    }
}
