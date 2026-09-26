<?php

namespace App\Console\Commands;

use App\Actions\SyncAutoShipperVehiclesAction;
use App\Models\AutoshipperSetting;
use App\Support\SqliteBusy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAutoShipperVehicles extends Command
{
    protected $signature = 'autoshipper:sync {--force : Run even when auto-sync is disabled in settings}';

    protected $description = 'Sync vehicles from AutoShipper API';

    public function handle(SyncAutoShipperVehiclesAction $action): int
    {
        $settings = AutoshipperSetting::current();

        if (! $this->option('force') && ! $settings->sync_enabled) {
            $this->info('AutoShipper auto sync is disabled in settings. Skipping.');

            Log::info('autoshipper:sync skipped: auto sync disabled');

            return self::SUCCESS;
        }

        $this->info('Syncing vehicles from AutoShipper...');

        try {
            $result = $action->execute();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            Log::error('autoshipper:sync failed', [
                'message' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $restorableCount = count($result['restorable']);
        $failed = (int) ($result['failed'] ?? 0);
        $this->info(
            "Fetched: {$result['total']}, Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}, Failed: {$failed}, Restorable: {$restorableCount}"
        );

        if ($failed > 0) {
            $this->warn("{$failed} vehicle(s) could not be written (see log). The rest of the batch completed.");
        }

        SqliteBusy::soft(fn () => $settings->update(['last_auto_sync_at' => now()]));

        Log::info('autoshipper:sync completed', [
            'total' => $result['total'],
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'failed' => $failed,
            'restorable' => $restorableCount,
        ]);

        // Individual row failures are already logged and counted; the batch as a
        // whole succeeded, so do not make the scheduler report a failed command.
        return self::SUCCESS;
    }
}
