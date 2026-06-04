<?php

namespace App\Actions;

use App\Enums\VehicleStatus;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Support\Facades\DB;

class AssignVehicleAction
{
    public function execute(Vehicle $vehicle, Dealer $dealer, User $admin): VehicleAssignment
    {
        return DB::transaction(function () use ($vehicle, $dealer, $admin) {
            VehicleAssignment::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'unassigned_at' => now(),
                ]);

            $assignment = VehicleAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'dealer_id' => $dealer->id,
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
                'is_active' => true,
            ]);

            $vehicle->update(['status' => VehicleStatus::Assigned]);

            return $assignment->load(['dealer.user', 'vehicle']);
        });
    }
}
