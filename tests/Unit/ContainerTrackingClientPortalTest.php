<?php

namespace Tests\Unit;

use App\Services\ContainerService;
use App\Services\ContainerTrackingService;
use App\Services\PortGeocoderService;
use App\Services\VinstackGalleryService;
use App\Services\VinstackService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ContainerTrackingClientPortalTest extends TestCase
{
    public function test_normalizes_client_portal_tracking_response(): void
    {
        $service = new ContainerTrackingService(
            vinstack: $this->createMock(VinstackService::class),
            gallery: $this->createMock(VinstackGalleryService::class),
            containers: $this->createMock(ContainerService::class),
            geocoder: $this->createMock(PortGeocoderService::class),
        );

        $sample = [
            'container_id' => '6a1ddc75ca45a2fa4a503441',
            'booking_number' => 'BKG123456',
            'container_number' => 'MSCU1234567',
            'tracking' => [
                'status' => 'IN_TRANSIT',
                'carrier' => 'Mediterranean Shipping Company (MSC)',
                'prepol' => [
                    'name' => 'Chicago',
                    'country' => 'United States',
                    'lat' => 41.8781,
                    'lng' => -87.6298,
                ],
                'pol' => [
                    'name' => 'Montreal',
                    'country' => 'Canada',
                    'lat' => 45.5017,
                    'lng' => -73.5673,
                ],
                'pod' => [
                    'name' => 'Jebel Ali',
                    'country' => 'United Arab Emirates',
                    'lat' => 25.0,
                    'lng' => 55.05,
                ],
                'transshipments' => [
                    [
                        'name' => 'Valencia',
                        'lat' => 39.4699,
                        'lng' => -0.3763,
                    ],
                ],
                'route' => [
                    ['lat' => 45.5017, 'lng' => -73.5673, 'name' => 'Montreal'],
                    ['lat' => 39.4699, 'lng' => -0.3763, 'name' => 'Valencia'],
                    ['lat' => 25.0, 'lng' => 55.05, 'name' => 'Jebel Ali'],
                ],
                'events' => [
                    [
                        'date' => '2025-01-10T08:00:00Z',
                        'actual' => true,
                        'description' => 'Export Loaded on Vessel',
                        'location' => [
                            'name' => 'Montreal',
                            'country' => 'Canada',
                            'lat' => 45.5017,
                            'lng' => -73.5673,
                        ],
                    ],
                    [
                        'date' => '2025-02-01T12:00:00Z',
                        'actual' => false,
                        'description' => 'Vessel Arrival',
                        'location' => [
                            'name' => 'Jebel Ali',
                            'country' => 'United Arab Emirates',
                            'lat' => 25.0,
                            'lng' => 55.05,
                        ],
                    ],
                ],
                'eta' => '2025-02-01T12:00:00Z',
            ],
        ];

        $container = [
            'id' => '6a1ddc75ca45a2fa4a503441',
            'booking_number' => 'BKG123456',
            'loading_point' => 'Chicago',
            'destination' => 'Jebel Ali',
            'shipping_line' => 'MSC',
        ];

        $method = new ReflectionMethod(ContainerTrackingService::class, 'normalizeClientPortalTracking');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'MSCU1234567', $container, $sample);

        $this->assertSame('client_portal', $result['source']);
        $this->assertSame('MSCU1234567', $result['container_number']);
        $this->assertSame('BKG123456', $result['booking_number']);
        $this->assertSame('MSC', $result['carrier']);
        $this->assertSame('in_transit', $result['status']);
        $this->assertSame('في الطريق', $result['status_label']);
        $this->assertSame('2025-02-01T12:00:00Z', $result['eta']);

        $this->assertSame('Chicago', $result['origin']['name']);
        $this->assertSame(41.8781, $result['origin']['lat']);
        $this->assertSame('Jebel Ali', $result['destination']['name']);
        $this->assertCount(1, $result['waypoints']);
        $this->assertSame('Valencia', $result['waypoints'][0]['name']);

        $this->assertCount(3, $result['route']);
        $this->assertSame([45.5017, -73.5673], $result['route'][0]);

        $this->assertNotNull($result['current_position']);
        $this->assertSame('Montreal', $result['current_position']['name']);
        $this->assertSame(45.5017, $result['current_position']['lat']);

        $this->assertCount(2, $result['events']);
        $this->assertSame('Export Loaded on Vessel', $result['events'][0]['title']);
        $this->assertSame('Montreal, Canada', $result['events'][0]['location']);
        $this->assertSame('actual', $result['events'][0]['type']);
        $this->assertSame('estimated', $result['events'][1]['type']);
    }
}
