<?php

namespace App\Actions;

use App\Models\Vehicle;

class RestoreVehicleAction
{
    public function execute(Vehicle $vehicle): Vehicle
    {
        $vehicle->restore();

        return $vehicle->fresh();
    }
}
