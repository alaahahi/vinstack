<?php

namespace App\Console\Commands;

use App\Services\VehiclePurchaseDateNormalizer;
use Illuminate\Console\Command;

class NormalizeVehiclePurchaseDates extends Command
{
    protected $signature = 'vehicles:normalize-purchase-dates';

    protected $description = 'Normalize vehicles.raw_data.purchase_date values to Y-m-d for chronological list sorting';

    public function handle(VehiclePurchaseDateNormalizer $normalizer): int
    {
        $result = $normalizer->normalizeAll();

        $this->info("Scanned {$result['scanned']} vehicles, updated {$result['updated']} purchase dates.");

        return self::SUCCESS;
    }
}
