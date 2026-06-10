<?php

namespace Tests\Unit;

use App\Support\VehicleImageStages;
use PHPUnit\Framework\TestCase;

class VehicleImageStagesTest extends TestCase
{
    public function test_resolves_client_portal_gallery_stages_from_urls_blocks(): void
    {
        $payload = [
            'urls' => [
                'https://cdn.example.com/autos/abc/terminal.jpg',
            ],
            'terminal' => [
                'urls' => [
                    'https://cdn.example.com/autos/abc/terminal.jpg',
                ],
            ],
            'pickup' => [
                'urls' => [
                    'https://cdn.example.com/autos/abc/pickup-1.jpg',
                    'https://cdn.example.com/autos/abc/pickup-2.jpg',
                ],
            ],
            'destination' => [
                'urls' => [],
            ],
        ];

        $stages = VehicleImageStages::resolve($payload);

        $this->assertCount(1, $stages['terminal']);
        $this->assertCount(2, $stages['pickup']);
        $this->assertSame([], $stages['destination']);
        $this->assertStringContainsString('terminal.jpg', $stages['terminal'][0]);
        $this->assertStringContainsString('pickup-1.jpg', $stages['pickup'][0]);
    }
}
