<?php

namespace App\Jobs;

use App\Services\ImageTransferProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessImageTransferBatch implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public int $transferJobId,
    ) {}

    public function handle(ImageTransferProcessor $processor): void
    {
        app(\App\Services\ImageTransferHealthService::class)->markQueueBatchRun($this->transferJobId);
        app(\App\Services\ImageTransferHealthService::class)->markBatchProcessed('queue', $this->transferJobId);

        $more = $processor->processBatch($this->transferJobId);

        if ($more) {
            self::dispatch($this->transferJobId)->delay(now()->addSecond());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = trim((string) ($exception?->getMessage() ?? ''));

        // SQLite locks are handled inside the processor (file progress); do not surface as hard fail.
        app(ImageTransferProcessor::class)->markFailedFromQueue(
            $this->transferJobId,
            $message !== ''
                ? 'فشل طابور النقل: '.$message
                : 'توقفت مهمة النقل بعد فشل الطابور.',
        );
    }
}
