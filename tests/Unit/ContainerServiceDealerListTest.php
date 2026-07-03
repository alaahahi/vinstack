<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\ContainerService;
use App\Services\VinstackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ContainerServiceDealerListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_dealer_sees_container_from_vehicle_raw_data_when_api_does_not_match(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109186',
            rawData: ['container_number' => 'MSKU 1234567', 'booking_number' => 'BK-001'],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'OTHER9999999',
                'booking_number' => 'BK-OTHER',
                'autos' => [['vin' => '1HGBH41JXMN109186']],
            ],
            [
                'container_number' => 'MSKU1234567',
                'booking_number' => 'BK-999',
                'autos' => [],
            ],
        ]);

        $rows = app(ContainerService::class)->listForDealer($dealer);

        $numbers = array_map(
            fn (array $row) => $row['container_number'] ?? null,
            $rows,
        );

        $this->assertContains('MSKU1234567', $numbers);
        $this->assertContains('OTHER9999999', $numbers);
        $this->assertGreaterThanOrEqual(2, count($rows));
    }

    public function test_dealer_sees_vehicle_derived_row_when_api_list_empty(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '5YJSA1E14HF000001',
            rawData: ['container_number' => 'TCLU0000001', 'destination' => 'Jebel Ali'],
        );

        $this->mockVinstackContainers([]);

        $rows = app(ContainerService::class)->listForDealer($dealer);

        $this->assertCount(1, $rows);
        $this->assertSame('TCLU0000001', $rows[0]['container_number']);
        $this->assertSame('vehicles', $rows[0]['source']);
        $this->assertSame('5YJSA1E14HF000001', $rows[0]['vehicles'][0]['vin']);
    }

    public function test_dealer_list_handles_array_destination_in_vehicle_raw_data(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109187',
            rawData: [
                'container_number' => 'MSKU 7654321',
                'destination' => ['name' => 'Jebel Ali', 'country' => 'UAE'],
                'loading_point' => ['label' => 'Houston Port'],
                'shipping_line' => ['value' => 'Maersk'],
            ],
        );

        $this->mockVinstackContainers([]);

        $rows = app(ContainerService::class)->listForDealer($dealer);

        $this->assertCount(1, $rows);
        $this->assertSame('MSKU 7654321', $rows[0]['container_number']);
        $this->assertSame('Jebel Ali', $rows[0]['destination']);
        $this->assertSame('Houston Port', $rows[0]['loading_point']);
        $this->assertSame('Maersk', $rows[0]['shipping_line']);
    }

    public function test_dealer_matches_vin_in_container_autos(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: 'WAUZZZ8V9KA000001',
            rawData: [],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'CMAU0000001',
                'autos' => [['vin' => 'wauzzz8v9ka000001', 'year' => 2019, 'make' => 'Audi', 'model' => 'A4']],
            ],
        ]);

        $rows = app(ContainerService::class)->listForDealer($dealer);

        $this->assertCount(1, $rows);
        $this->assertSame('CMAU0000001', $rows[0]['container_number']);
        $this->assertSame('vinstack', $rows[0]['source']);
    }

    public function test_admin_sees_manual_vehicle_container_when_not_in_vinstack_api(): void
    {
        Vehicle::query()->create([
            'vinstack_id' => null,
            'vin' => '1FA6P8TH4G5200001',
            'make' => 'Ford',
            'model' => 'Mustang',
            'year' => 2020,
            'source' => VehicleSource::Manual,
            'status' => VehicleStatus::Available,
            'raw_data' => [
                'container_number' => 'MANU1234567',
                'booking_number' => 'BK-MANUAL',
                'destination' => 'Dubai',
            ],
        ]);

        $this->mockVinstackContainers([]);

        $rows = app(ContainerService::class)->listForAdminFiltered();

        $numbers = array_map(
            fn (array $row) => $row['container_number'] ?? null,
            $rows,
        );

        $this->assertContains('MANU1234567', $numbers);
        $this->assertSame('vehicles', $rows[0]['source']);
    }

    public function test_vehicles_for_container_returns_enriched_db_vehicle(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109188',
            rawData: [
                'container_number' => 'VEH1234567',
                'lot' => '445566',
                'auction' => 'Copart',
                'destination' => 'Dubai',
                'purchase_date' => '2024-03-15',
            ],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'VEH1234567',
                'booking_number' => 'BK-VEH',
                'invoice_ref' => 'INV-99',
                'autos' => [
                    ['vin' => '1HGBH41JXMN109188', 'year' => 2021, 'make' => 'Toyota', 'model' => 'Camry'],
                ],
            ],
        ]);

        $payload = app(ContainerService::class)->vehiclesForContainer('VEH1234567', $dealer);

        $this->assertNotNull($payload);
        $this->assertSame('VEH1234567', $payload['container']['container_number']);
        $this->assertCount(1, $payload['vehicles']);
        $this->assertSame('1HGBH41JXMN109188', $payload['vehicles'][0]['vin']);
        $this->assertSame('445566', $payload['vehicles'][0]['lot']);
        $this->assertSame('Copart', $payload['vehicles'][0]['auction']);
    }

    public function test_image_lookup_keys_resolve_booking_to_vinstack_container_number(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109188',
            rawData: ['booking_number' => 'BK-DEALER-ONLY'],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'MSCU9999999',
                'booking_number' => 'BK-DEALER-ONLY',
                'autos' => [
                    ['vin' => '1HGBH41JXMN109188', 'year' => 2021, 'make' => 'Toyota', 'model' => 'Camry'],
                ],
            ],
        ]);

        $service = app(ContainerService::class);
        $keys = $service->imageLookupKeysForContainer('BK-DEALER-ONLY', [
            'container_number' => null,
            'booking_number' => 'BK-DEALER-ONLY',
        ]);

        $this->assertContains('BK-DEALER-ONLY', $keys);
        $this->assertContains('MSCU9999999', $keys);
    }

    public function test_list_includes_image_count_and_thumbnail_from_batched_lookup(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109199',
            rawData: ['container_number' => 'IMG1234567', 'booking_number' => 'BK-IMG'],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'IMG1234567',
                'booking_number' => 'BK-IMG',
                'autos' => [
                    ['vin' => '1HGBH41JXMN109199', 'year' => 2022, 'make' => 'Honda', 'model' => 'Civic'],
                ],
            ],
        ]);

        \App\Models\ContainerImage::query()->create([
            'container_number' => 'IMG1234567',
            'original_name' => 'first.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/first.jpg',
            'public_id' => 'vinstack/containers/IMG1234567/first-1',
            'uploaded_at' => now(),
        ]);

        \App\Models\ContainerImage::query()->create([
            'container_number' => 'IMG1234567',
            'original_name' => 'second.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/second.jpg',
            'public_id' => 'vinstack/containers/IMG1234567/second-2',
            'uploaded_at' => now(),
        ]);

        $rows = app(ContainerService::class)->listForDealer($dealer);
        $row = collect($rows)->firstWhere('container_number', 'IMG1234567');

        $this->assertNotNull($row);
        $this->assertSame(2, $row['image_count']);
        $this->assertSame('https://res.cloudinary.com/demo/first.jpg', $row['thumbnail_url']);
    }

    public function test_list_resolves_images_stored_under_booking_number(): void
    {
        $dealer = $this->createDealerWithAssignment(
            vin: '1HGBH41JXMN109200',
            rawData: ['booking_number' => 'BK-ONLY-IMG'],
        );

        $this->mockVinstackContainers([
            [
                'container_number' => 'CNTRONLYIMG',
                'booking_number' => 'BK-ONLY-IMG',
                'autos' => [
                    ['vin' => '1HGBH41JXMN109200', 'year' => 2020, 'make' => 'BMW', 'model' => 'X5'],
                ],
            ],
        ]);

        \App\Models\ContainerImage::query()->create([
            'container_number' => 'BK-ONLY-IMG',
            'original_name' => 'booking.jpg',
            'cloudinary_url' => 'https://res.cloudinary.com/demo/booking.jpg',
            'public_id' => 'vinstack/containers/BK-ONLY-IMG/booking-1',
            'uploaded_at' => now(),
        ]);

        $rows = app(ContainerService::class)->listForDealer($dealer);
        $row = collect($rows)->firstWhere('booking_number', 'BK-ONLY-IMG');

        $this->assertNotNull($row);
        $this->assertSame(1, $row['image_count']);
        $this->assertSame('https://res.cloudinary.com/demo/booking.jpg', $row['thumbnail_url']);
    }

    public function test_admin_merges_manual_vehicle_container_with_vinstack_api(): void
    {
        Vehicle::query()->create([
            'vinstack_id' => null,
            'vin' => '1FA6P8TH4G5200002',
            'make' => 'Ford',
            'model' => 'Focus',
            'year' => 2019,
            'source' => VehicleSource::Manual,
            'status' => VehicleStatus::Available,
            'raw_data' => ['container_number' => 'ONLYMANUAL1'],
        ]);

        $this->mockVinstackContainers([
            ['container_number' => 'API0000001', 'autos' => []],
        ]);

        $rows = app(ContainerService::class)->listForAdminFiltered();
        $numbers = array_map(
            fn (array $row) => $row['container_number'] ?? null,
            $rows,
        );

        $this->assertContains('API0000001', $numbers);
        $this->assertContains('ONLYMANUAL1', $numbers);
        $this->assertGreaterThanOrEqual(2, count($rows));
    }

    /**
     * @param  array<string, mixed>  $rawData
     */
    protected function createDealerWithAssignment(string $vin, array $rawData): Dealer
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Test Dealer',
        ]);

        $vehicle = Vehicle::query()->create([
            'vinstack_id' => 'vs-test-'.uniqid(),
            'vin' => $vin,
            'source' => VehicleSource::Vinstack,
            'status' => VehicleStatus::Assigned,
            'raw_data' => $rawData,
        ]);

        VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        return $dealer;
    }

    /**
     * @param  list<array<string, mixed>>  $containers
     */
    protected function mockVinstackContainers(array $containers): void
    {
        $mock = Mockery::mock(VinstackService::class);
        $mock->shouldReceive('containers')->andReturn($containers);
        $mock->shouldReceive('container')
            ->andReturnUsing(function (string $number) use ($containers) {
                $normalized = strtoupper(preg_replace('/\s+/', '', $number) ?? '');

                foreach ($containers as $row) {
                    $rowNumber = strtoupper(preg_replace('/\s+/', '', (string) ($row['container_number'] ?? '')));

                    if ($rowNumber === $normalized) {
                        return $row;
                    }
                }

                throw new \RuntimeException('Container not found');
            });

        $this->app->instance(VinstackService::class, $mock);
    }
}
