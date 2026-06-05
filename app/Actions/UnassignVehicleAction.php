<?php

namespace App\Actions;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Support\Facades\DB;

class UnassignVehicleAction
{
    public function execute(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            VehicleAssignment::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'unassigned_at' => now(),
                ]);

            $vehicle->update(['status' => VehicleStatus::Available]);
        });
    }
}
