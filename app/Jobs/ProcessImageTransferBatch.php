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
        $more = $processor->processBatch($this->transferJobId);

        if ($more) {
            self::dispatch($this->transferJobId)->delay(now()->addSecond());
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = trim((string) ($exception?->getMessage() ?? ''));

        app(ImageTransferProcessor::class)->markFailedFromQueue(
            $this->transferJobId,
            $message !== ''
                ? 'فشل طابور النقل: '.$message
                : 'توقفت مهمة النقل بعد فشل الطابور.',
        );
    }
}
