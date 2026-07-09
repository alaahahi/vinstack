<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerVehicleIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_list_orders_by_purchase_date_newest_first(): void
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
            'raw_data' => ['purchase_date' => '2026-06-01'],
        ]);

        $newerPurchase = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dealer-order-2',
            'vin' => '1HGCM82633A004372',
            'status' => VehicleStatus::Assigned,
            'raw_data' => ['purchase_date' => '2026-07-01'],
        ]);

        $fallbackCreated = Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => 'dealer-order-3',
            'vin' => '1HGCM82633A004373',
            'status' => VehicleStatus::Assigned,
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

        $vins = Vehicle::query()
            ->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            })
            ->newestFirst()
            ->pluck('vin')
            ->take(3)
            ->values()
            ->all();

        $this->assertSame([
            $fallbackCreated->vin,
            $newerPurchase->vin,
            $olderPurchase->vin,
        ], $vins);
    }
}
