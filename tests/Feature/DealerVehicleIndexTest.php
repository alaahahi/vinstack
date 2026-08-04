<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\VehicleUploadedImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DealerVehicleIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(VehicleUploadedImageService::class, function ($mock): void {
            $mock->shouldReceive('enrichListVehicle')
                ->andReturnUsing(fn (Vehicle $vehicle) => $vehicle->toArray());
        });
    }

    public function test_dealer_list_orders_by_purchase_date_newest_first_and_includes_price(): void
    {
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);
        $dealer = Dealer::query()->create([
            'user_id' => $dealerUser->id,
            'company_name' => 'Dealer Test',
        ]);

        $olderPurchase = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dealer-order-1',
            'vin' => '1HGCM82633A004371',
            'status' => VehicleStatus::Assigned,
            'price' => 7000,
            'raw_data' => [
                'purchase_date' => '2026-06-01',
                'value' => '6800',
            ],
        ]);

        $newerPurchase = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dealer-order-2',
            'vin' => '1HGCM82633A004372',
            'status' => VehicleStatus::Assigned,
            'price' => 11000,
            'raw_data' => [
                'purchase_date' => '2026-07-01',
                'value' => '10900',
            ],
        ]);

        $fallbackCreated = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dealer-order-3',
            'vin' => '1HGCM82633A004373',
            'status' => VehicleStatus::Assigned,
            'price' => 13000,
            'raw_data' => [],
        ]);

        $olderPurchase->update(['created_at' => now()->subDays(3)]);
        $newerPurchase->update(['created_at' => now()->subDays(2)]);
        $fallbackCreated->update(['created_at' => now()]);

        foreach ([$olderPurchase, $newerPurchase, $fallbackCreated] as $vehicle) {
            VehicleAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'dealer_id' => $dealer->id,
                'assigned_by' => $dealerUser->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);
        }

        Sanctum::actingAs($dealerUser);

        $response = $this->getJson('/api/dealer/vehicles?per_page=50');

        $response->assertOk();

        $vins = collect($response->json('data'))->pluck('vin')->all();

        $this->assertSame([
            $newerPurchase->vin,
            $olderPurchase->vin,
            $fallbackCreated->vin,
        ], $vins);

        $byVin = collect($response->json('data'))->keyBy('vin');

        $this->assertSame('11000.00', $byVin[$newerPurchase->vin]['price']);
        $this->assertSame('10900', $byVin[$newerPurchase->vin]['raw_data']['value']);
        $this->assertSame('13000.00', $byVin[$fallbackCreated->vin]['price']);
    }
}
