<?php

namespace App\Console\Commands;

use App\Services\ImageTransferHealthService;
use App\Services\ImageTransferProcessor;
use Illuminate\Console\Command;

class ProcessImageTransfers extends Command
{
    protected $signature = 'image-transfers:process {--limit=5 : Max jobs to touch per run}';

    protected $description = 'Process pending image transfer jobs in batches (fallback when queue worker is idle)';

    public function handle(ImageTransferProcessor $processor): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $count = $processor->processPendingJobs($limit);

        app(ImageTransferHealthService::class)->markSchedulerRun($count);

        $this->info("Processed {$count} image transfer job(s).");

        return self::SUCCESS;
    }
}
