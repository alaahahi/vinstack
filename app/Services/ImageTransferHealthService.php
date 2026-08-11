<?php

namespace App\Services;

use App\Jobs\ProcessImageTransferBatch;
use App\Models\ImageTransferJob;
use App\Models\VinstackSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImageTransferHealthService
{
    public const SCHEDULER_FRESH_SECONDS = 120;

    public const QUEUE_FRESH_SECONDS = 180;

    protected function healthPath(): string
    {
        return storage_path('app/image-transfers/health.json');
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    public function touch(array $patch): void
    {
        $dir = dirname($this->healthPath());

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $current = $this->readRaw();
        $merged = array_merge($current, $patch, [
            'updated_at' => now()->toIso8601String(),
        ]);

        File::put($this->healthPath(), json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function markSchedulerRun(int $touchedJobs): void
    {
        $this->touch([
            'scheduler_last_run_at' => now()->toIso8601String(),
            'scheduler_last_touched' => $touchedJobs,
            'scheduler_command' => 'image-transfers:process',
        ]);
    }

    public function markQueueBatchRun(int $jobId): void
    {
        $this->touch([
            'queue_last_run_at' => now()->toIso8601String(),
            'queue_last_job_id' => $jobId,
            'queue_job' => ProcessImageTransferBatch::class,
        ]);
    }

    public function markBatchProcessed(string $source, int $jobId): void
    {
        $this->touch([
            'batch_last_run_at' => now()->toIso8601String(),
            'batch_last_source' => $source,
            'batch_last_job_id' => $jobId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $raw = $this->readRaw();
        $settings = VinstackSetting::current();
        $queueConnection = (string) config('queue.default', 'sync');

        $schedulerLast = $this->parseTime($raw['scheduler_last_run_at'] ?? null);
        $queueLast = $this->parseTime($raw['queue_last_run_at'] ?? null);
        $batchLast = $this->parseTime($raw['batch_last_run_at'] ?? null);

        $pendingQueueJobs = 0;
        $failedQueueJobs = 0;

        try {
            if ($queueConnection === 'database') {
                $pendingQueueJobs = (int) DB::table('jobs')->count();
                $failedQueueJobs = (int) DB::table('failed_jobs')->count();
            }
        } catch (\Throwable) {
            // Ignore missing queue tables in partial installs.
        }

        $activeTransfers = ImageTransferJob::query()
            ->whereIn('status', [
                ImageTransferJob::STATUS_QUEUED,
                ImageTransferJob::STATUS_PROCESSING,
            ])
            ->count();

        $schedulerOk = $schedulerLast !== null
            && $schedulerLast->greaterThan(now()->subSeconds(self::SCHEDULER_FRESH_SECONDS));

        $queueOk = $queueConnection === 'sync'
            || (
                $queueLast !== null
                && $queueLast->greaterThan(now()->subSeconds(self::QUEUE_FRESH_SECONDS))
            )
            || ($pendingQueueJobs === 0 && $activeTransfers === 0);

        // Sync queue always "works" inline; database queue needs a worker heartbeat when busy.
        if ($queueConnection === 'database' && $activeTransfers > 0 && $pendingQueueJobs > 0) {
            $queueOk = $queueLast !== null
                && $queueLast->greaterThan(now()->subSeconds(self::QUEUE_FRESH_SECONDS));
        }

        return [
            'async_enabled' => (bool) ($settings->image_transfer_async_enabled ?? true),
            'batch_size' => max(1, (int) ($settings->image_transfer_batch_size ?? 10)),
            'queue_connection' => $queueConnection,
            'pending_queue_jobs' => $pendingQueueJobs,
            'failed_queue_jobs' => $failedQueueJobs,
            'active_transfers' => $activeTransfers,
            'scheduler' => [
                'command' => 'image-transfers:process',
                'frequency' => 'everyMinute',
                'ok' => $schedulerOk,
                'last_run_at' => $schedulerLast?->toIso8601String(),
                'last_touched' => (int) ($raw['scheduler_last_touched'] ?? 0),
                'hint' => $schedulerOk
                    ? null
                    : 'شغّل الجدولة كل دقيقة: php artisan schedule:run (أو Windows Task Scheduler / cron).',
            ],
            'queue' => [
                'job' => 'ProcessImageTransferBatch',
                'ok' => $queueOk,
                'last_run_at' => $queueLast?->toIso8601String(),
                'last_job_id' => $raw['queue_last_job_id'] ?? null,
                'hint' => $queueOk
                    ? null
                    : ($queueConnection === 'sync'
                        ? null
                        : 'شغّل عامل الطابور: php artisan queue:work'),
            ],
            'batch' => [
                'last_run_at' => $batchLast?->toIso8601String(),
                'last_source' => $raw['batch_last_source'] ?? null,
                'last_job_id' => $raw['batch_last_job_id'] ?? null,
            ],
            'overall_ok' => $schedulerOk && $queueOk,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function readRaw(): array
    {
        $path = $this->healthPath();

        if (! File::exists($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string) File::get($path), true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function parseTime(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
