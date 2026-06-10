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

        $this->app->instance(VinstackService::class, $mock);
    }
}
