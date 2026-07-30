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

    public function test_current_position_uses_latest_actual_event_not_oldest(): void
    {
        $service = new ContainerTrackingService(
            vinstack: $this->createMock(VinstackService::class),
            gallery: $this->createMock(VinstackGalleryService::class),
            containers: $this->createMock(ContainerService::class),
            geocoder: $this->createMock(PortGeocoderService::class),
        );

        $sample = [
            'container_number' => 'MEDU9572606',
            'tracking' => [
                'status' => 'IN_TRANSIT',
                'prepol' => ['name' => 'Vancouver', 'country' => 'Canada', 'lat' => 49.25, 'lng' => -123.12],
                'pol' => ['name' => 'Vancouver', 'country' => 'Canada', 'lat' => 49.25, 'lng' => -123.12, 'actual' => true],
                'pod' => ['name' => 'Mersin', 'country' => 'Turkey', 'lat' => 36.81, 'lng' => 34.64],
                'route' => [
                    ['lat' => 49.25, 'lng' => -123.12, 'name' => 'Vancouver'],
                    ['lat' => 45.51, 'lng' => -73.57, 'name' => 'Montreal'],
                    ['lat' => 37.96, 'lng' => -8.87, 'name' => 'Sines'],
                    ['lat' => 36.81, 'lng' => 34.64, 'name' => 'Mersin'],
                ],
                'events' => [
                    [
                        'date' => '2026-06-18T00:00:00Z',
                        'actual' => false,
                        'description' => 'Vessel arrival',
                        'location' => ['name' => 'Gioia Tauro', 'country' => 'Italy', 'lat' => 38.43, 'lng' => 15.90],
                    ],
                    [
                        'date' => '2026-06-01T00:00:00Z',
                        'actual' => true,
                        'description' => 'Export Loaded on Vessel',
                        'location' => ['name' => 'Montreal', 'country' => 'Canada', 'lat' => 45.51, 'lng' => -73.57],
                    ],
                    [
                        'date' => '2026-05-15T00:00:00Z',
                        'actual' => true,
                        'description' => 'Empty to Shipper',
                        'location' => ['name' => 'Vancouver', 'country' => 'Canada', 'lat' => 49.25, 'lng' => -123.12],
                    ],
                ],
                'last_event_date' => '2026-06-01 00:00:00',
                'next_event_date' => '2026-06-12 16:30:00',
            ],
        ];

        $method = new ReflectionMethod(ContainerTrackingService::class, 'normalizeClientPortalTracking');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'MEDU9572606', null, $sample);

        $this->assertNotNull($result['current_position']);
        $this->assertSame('Montreal', $result['current_position']['name']);
        $this->assertSame(45.51, $result['current_position']['lat']);
        $this->assertSame(-73.57, $result['current_position']['lng']);
    }

    public function test_derived_tracking_uses_straight_route_and_estimated_midpoint(): void
    {
        $geocoder = $this->createMock(PortGeocoderService::class);
        $geocoder->method('resolve')->willReturnCallback(function (string $label): ?array {
            return match ($label) {
                'Montreal' => [
                    'name' => 'Montreal, CA',
                    'lat' => 45.5017,
                    'lng' => -73.5673,
                    'geocoded' => false,
                    'geocode_confidence' => 'high',
                    'geocode_provider' => 'known_ports',
                ],
                'Jebel Ali' => [
                    'name' => 'Jebel Ali, AE',
                    'lat' => 25.0260,
                    'lng' => 55.0610,
                    'geocoded' => false,
                    'geocode_confidence' => 'high',
                    'geocode_provider' => 'known_ports',
                ],
                default => null,
            };
        });

        $service = new ContainerTrackingService(
            vinstack: $this->createMock(VinstackService::class),
            gallery: $this->createMock(VinstackGalleryService::class),
            containers: $this->createMock(ContainerService::class),
            geocoder: $geocoder,
        );

        $container = [
            'container_number' => 'MSCU1234567',
            'loading_point' => 'Montreal',
            'destination' => 'Jebel Ali',
            'shipping_line' => 'MSC',
            'loading_date' => '2026-07-01',
            'eta' => '2099-08-01',
        ];

        $method = new ReflectionMethod(ContainerTrackingService::class, 'buildDerived');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'MSCU1234567', $container);

        $this->assertSame('derived', $result['source']);
        $this->assertTrue($result['route_is_estimated']);
        $this->assertSame([
            [45.5017, -73.5673],
            [25.0260, 55.0610],
        ], $result['route']);
        $this->assertNotNull($result['current_position']);
        $this->assertSame('Estimated current position', $result['current_position']['name']);
        $this->assertSame(45.5017, $result['origin']['lat']);
        $this->assertSame(25.0260, $result['destination']['lat']);
    }
}
