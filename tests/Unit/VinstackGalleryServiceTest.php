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

    public function test_stages_changed_detects_replaced_urls_with_same_count(): void
    {
        $service = new VinstackGalleryService(
            uploadedImages: $this->createMock(\App\Services\VehicleUploadedImageService::class),
        );

        $method = new \ReflectionMethod(VinstackGalleryService::class, 'stagesChanged');
        $method->setAccessible(true);

        $before = [
            'terminal' => ['https://cdn.example/old-1.jpg'],
            'pickup' => [],
            'destination' => [],
        ];
        $after = [
            'terminal' => ['https://cdn.example/new-1.jpg'],
            'pickup' => [],
            'destination' => [],
        ];

        $this->assertTrue($method->invoke($service, $before, $after));
    }
}
