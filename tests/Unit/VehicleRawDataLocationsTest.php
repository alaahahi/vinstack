<?php

namespace Tests\Unit;

use App\Support\VehicleRawDataLocations;
use Tests\TestCase;

class VehicleRawDataLocationsTest extends TestCase
{
    public function test_rejects_dot_joined_image_filename_chain(): void
    {
        $value = 'images-1777574841795-37632154.jpeg.images-1777574841796-877959842.jpeg';

        $this->assertTrue(VehicleRawDataLocations::isImageLikeString($value));
        $this->assertNull(VehicleRawDataLocations::locationLabel($value));
    }

    public function test_recovers_destination_from_pod_when_primary_is_gallery_block(): void
    {
        $raw = [
            'destination' => [
                'urls' => [
                    'https://cdn.example.com/images-1700000000000-a.jpeg',
                    'https://cdn.example.com/images-1700000000001-b.jpeg',
                ],
            ],
            'pod' => 'Mersin',
            'loading_point' => 'Toronto',
        ];

        $sanitized = VehicleRawDataLocations::sanitizeForList($raw);

        $this->assertSame('Mersin', $sanitized['destination']);
        $this->assertSame('Toronto', $sanitized['loading_point']);
        $this->assertArrayHasKey('urls', $raw['destination']);
    }

    public function test_clears_corrupted_loading_point_without_fallback(): void
    {
        $raw = [
            'loading_point' => 'images-1777574841795-37632154.jpeg.images-1777574841796-877959842.jpeg',
            'destination' => 'Jebel Ali',
        ];

        $sanitized = VehicleRawDataLocations::sanitizeForList($raw);

        $this->assertArrayNotHasKey('loading_point', $sanitized);
        $this->assertSame('Jebel Ali', $sanitized['destination']);
    }
}
