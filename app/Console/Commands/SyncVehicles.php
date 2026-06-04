<?php

namespace App\Console\Commands;

use App\Actions\SyncVehiclesAction;
use Illuminate\Console\Command;

class SyncVehicles extends Command
{
    protected $signature = 'vinstack:sync';

    protected $description = 'Sync vehicles from Vinstack API';

    public function handle(SyncVehiclesAction $action): int
    {
        $this->info('Syncing vehicles from Vinstack...');

        try {
            $result = $action->execute();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $restorableCount = count($result['restorable']);
        $this->info("Fetched: {$result['total']}, Created: {$result['created']}, Updated: {$result['updated']}, Restorable: {$restorableCount}");

        return self::SUCCESS;
    }
}
