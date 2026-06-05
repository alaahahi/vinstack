<?php

namespace App\Console\Commands;

use App\Actions\SyncVehiclesAction;
use App\Models\VinstackSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVehicles extends Command
{
    protected $signature = 'vinstack:sync {--force : Run even when auto-sync is disabled in settings}';

    protected $description = 'Sync vehicles from Vinstack API';

    public function handle(SyncVehiclesAction $action): int
    {
        $settings = VinstackSetting::current();

        if (! $this->option('force') && ! $settings->sync_enabled) {
            $this->info('Auto sync is disabled in settings. Skipping.');

            Log::info('vinstack:sync skipped: auto sync disabled');

            return self::SUCCESS;
        }

        if (! $this->hasApiToken($settings)) {
            $this->warn('Vinstack API token is not configured. Skipping sync.');

            Log::warning('vinstack:sync skipped: API token not configured');

            return self::SUCCESS;
        }

        $this->info('Syncing vehicles from Vinstack...');

        try {
            $result = $action->execute();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            Log::error('vinstack:sync failed', [
                'message' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $restorableCount = count($result['restorable']);
        $this->info("Fetched: {$result['total']}, Created: {$result['created']}, Updated: {$result['updated']}, Restorable: {$restorableCount}");

        $settings->update(['last_auto_sync_at' => now()]);

        Log::info('vinstack:sync completed', [
            'total' => $result['total'],
            'created' => $result['created'],
            'updated' => $result['updated'],
            'restorable' => $restorableCount,
        ]);

        return self::SUCCESS;
    }

    protected function hasApiToken(VinstackSetting $settings): bool
    {
        return (bool) ($settings->api_token ?: config('services.vinstack.token'));
    }
}
