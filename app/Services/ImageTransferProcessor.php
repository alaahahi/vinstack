<?php

namespace App\Services;

use App\Jobs\ProcessImageTransferBatch;
use App\Models\ContainerImage;
use App\Models\ImageTransferJob;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VinstackSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDOException;

class ImageTransferProcessor
{
    public function __construct(
        protected ContainerImageService $images,
        protected ContainerService $containers,
        protected DealerNotificationService $notifications,
        protected VehicleUploadedImageService $vehicleImages,
        protected ImageTransferHealthService $health,
        protected ImageTransferProgressStore $progress,
    ) {}

    /**
     * Process one batch for a transfer job. Returns true when more work remains.
     *
     * Progress is file-backed during uploads so SQLite is not locked for Cloudinary I/O.
     */
    public function processBatch(int $jobId): bool
    {
        $job = ImageTransferJob::query()->find($jobId);

        if ($job === null || $job->isFinished()) {
            return false;
        }

        if ($job->status === ImageTransferJob::STATUS_CANCELLED) {
            return false;
        }

        $this->progress->applyToJob($job);

        if ($job->isFinished()) {
            $this->trySyncJobToDatabase($job);

            return false;
        }

        if ($job->status === ImageTransferJob::STATUS_QUEUED) {
            $job->status = ImageTransferJob::STATUS_PROCESSING;
            $job->started_at = $job->started_at ?? now();

            if ($job->type === ImageTransferJob::TYPE_CONTAINER_ZIP && $job->replace_existing) {
                $this->tryDeleteExistingContainerImages((string) $job->container_number);
            }

            $this->persistProgress($job);
            $this->trySyncJobToDatabase($job);
        }

        $batchSize = max(1, (int) (VinstackSetting::current()->image_transfer_batch_size ?? 10));
        $manifest = $job->manifest ?? [];
        $processed = 0;

        foreach ($manifest as $offset => $item) {
            if (($item['status'] ?? '') !== 'pending') {
                continue;
            }

            if ($processed >= $batchSize) {
                break;
            }

            try {
                $this->processManifestItem($job, $item);
                $manifest[$offset]['status'] = 'done';
                $manifest[$offset]['error'] = null;
            } catch (\Throwable $e) {
                Log::warning('Image transfer batch item failed', [
                    'job' => $job->uuid,
                    'type' => $job->type,
                    'name' => $item['name'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                // SQLite lock after a successful Cloudinary upload is transient: keep pending.
                if ($this->isDatabaseLock($e)) {
                    Log::warning('Image transfer item deferred due to sqlite lock', [
                        'job' => $job->uuid,
                        'name' => $item['name'] ?? null,
                    ]);
                    $manifest[$offset]['status'] = 'pending';
                    $manifest[$offset]['error'] = null;
                } else {
                    $manifest[$offset]['status'] = 'failed';
                    $manifest[$offset]['error'] = $e->getMessage();
                }
            }

            $processed++;
            $job->manifest = array_values($manifest);
            $job->recalculateCountersFromManifest();
            $this->persistProgress($job);
        }

        $job->manifest = array_values($manifest);
        $job->recalculateCountersFromManifest();

        if ($job->hasPendingManifestItems()) {
            $job->status = ImageTransferJob::STATUS_PROCESSING;
            $this->persistProgress($job);
            $this->trySyncJobToDatabase($job);
            $this->health->markBatchProcessed('processor', $jobId);

            return true;
        }

        $this->finalizeJob($job);
        $this->health->markBatchProcessed('processor', $jobId);

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function processManifestItem(ImageTransferJob $job, array $item): void
    {
        if ($job->type === ImageTransferJob::TYPE_CONTAINER_ZIP) {
            $this->images->uploadStagedImage((string) $job->container_number, $item);

            return;
        }

        if (! in_array($job->type, [
            ImageTransferJob::TYPE_VEHICLE_ZIP,
            ImageTransferJob::TYPE_VEHICLE_IMAGES,
        ], true)) {
            throw new \RuntimeException('Unsupported transfer type: '.$job->type);
        }

        $vehicle = $job->vehicle_id
            ? Vehicle::query()->find($job->vehicle_id)
            : null;

        if ($vehicle === null) {
            throw new \RuntimeException('Vehicle not found for transfer job.');
        }

        $user = $job->user_id
            ? User::query()->find($job->user_id)
            : null;

        if ($user === null) {
            throw new \RuntimeException('Uploader user not found for transfer job.');
        }

        $stage = (string) ($job->stage ?? '');

        if ($stage === '') {
            throw new \RuntimeException('Image stage is required for vehicle transfer.');
        }

        $this->vehicleImages->storeFromPath(
            $vehicle,
            $stage,
            (string) ($item['path'] ?? ''),
            (string) ($item['name'] ?? 'image.jpg'),
            $user,
            discardAfterUpload: false,
        );
    }

    public function processPendingJobs(int $limit = 5): int
    {
        $this->reclaimStaleJobs();

        $jobs = ImageTransferJob::query()
            ->whereIn('status', [
                ImageTransferJob::STATUS_QUEUED,
                ImageTransferJob::STATUS_PROCESSING,
            ])
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $touched = 0;

        foreach ($jobs as $jobId) {
            $more = $this->processBatch((int) $jobId);
            $touched++;

            if ($more) {
                ProcessImageTransferBatch::dispatch((int) $jobId);
            }
        }

        return $touched;
    }

    public function processNow(ImageTransferJob $job): ImageTransferJob
    {
        if ($job->isFinished()) {
            return $job->fresh() ?? $job;
        }

        if ($job->status === ImageTransferJob::STATUS_CANCELLED) {
            return $job;
        }

        $more = $this->processBatch($job->id);

        if ($more) {
            ProcessImageTransferBatch::dispatch($job->id);
        }

        return $this->freshWithProgress($job->id) ?? $job;
    }

    public function cancel(ImageTransferJob $job): ImageTransferJob
    {
        $job = ImageTransferJob::query()->find($job->id) ?? $job;

        if ($job->isFinished()) {
            return $job;
        }

        $this->progress->applyToJob($job);

        $job->status = ImageTransferJob::STATUS_CANCELLED;
        $job->error_message = $job->error_message
            ?: 'تم إلغاء مهمة النقل بواسطة الإدارة.';
        $job->finished_at = now();
        $this->persistProgress($job);
        $this->trySyncJobToDatabase($job);
        $this->cleanupStaging($job);
        $this->progress->forget($job);

        return $job->fresh() ?? $job;
    }

    public function retryFailed(ImageTransferJob $job): ImageTransferJob
    {
        if (! in_array($job->status, [
            ImageTransferJob::STATUS_FAILED,
            ImageTransferJob::STATUS_PARTIAL,
        ], true)) {
            throw new \RuntimeException('لا يمكن إعادة محاولة هذه المهمة في حالتها الحالية.');
        }

        $this->progress->applyToJob($job);
        $manifest = $job->manifest ?? [];

        foreach ($manifest as $offset => $item) {
            if (($item['status'] ?? '') === 'failed') {
                $manifest[$offset]['status'] = 'pending';
                $manifest[$offset]['error'] = null;
            }
        }

        $job->manifest = array_values($manifest);
        $job->recalculateCountersFromManifest();
        $job->status = ImageTransferJob::STATUS_QUEUED;
        $job->error_message = null;
        $job->finished_at = null;
        $this->persistProgress($job);
        $this->trySyncJobToDatabase($job);

        $more = $this->processBatch($job->id);

        if ($more) {
            ProcessImageTransferBatch::dispatch($job->id);
        }

        return $this->freshWithProgress($job->id) ?? $job;
    }

    public function markFailedFromQueue(int $jobId, string $message): void
    {
        // Database locks are not permanent transfer failures — keep file progress as processing.
        // schedule:run / processPendingJobs will continue; no forced retry queue.
        if ($this->messageIsDatabaseLock($message)) {
            Log::warning('Image transfer queue lock ignored; progress kept on disk', [
                'job_id' => $jobId,
                'message' => $message,
            ]);

            $job = ImageTransferJob::query()->find($jobId);

            if ($job !== null && ! $job->isFinished()) {
                $this->progress->applyToJob($job);
                $job->status = ImageTransferJob::STATUS_PROCESSING;
                $this->trySyncJobToDatabase($job);
            }

            return;
        }

        $job = ImageTransferJob::query()->find($jobId);

        if ($job === null || $job->isFinished()) {
            return;
        }

        $this->progress->applyToJob($job);
        $job->status = ImageTransferJob::STATUS_FAILED;
        $job->error_message = $message !== ''
            ? $message
            : 'توقفت مهمة النقل بعد فشل الطابور.';
        $job->finished_at = now();
        $this->persistProgress($job);
        $this->trySyncJobToDatabase($job);
    }

    protected function reclaimStaleJobs(): void
    {
        $stale = ImageTransferJob::query()
            ->whereIn('status', [
                ImageTransferJob::STATUS_QUEUED,
                ImageTransferJob::STATUS_PROCESSING,
            ])
            ->where('updated_at', '<=', now()->subMinutes(ImageTransferJob::STALE_AFTER_MINUTES))
            ->orderBy('id')
            ->limit(20)
            ->get();

        foreach ($stale as $job) {
            ProcessImageTransferBatch::dispatch($job->id);
        }
    }

    protected function finalizeJob(ImageTransferJob $job): void
    {
        if ($job->status === ImageTransferJob::STATUS_CANCELLED) {
            return;
        }

        if ($job->transferred_count <= 0 && $job->failed_count > 0) {
            $job->status = ImageTransferJob::STATUS_FAILED;
            $job->error_message = 'فشل نقل جميع الصور إلى Cloudinary.';
        } elseif ($job->failed_count > 0) {
            $job->status = ImageTransferJob::STATUS_PARTIAL;
            $job->error_message = "اكتمل جزئياً — نجح {$job->transferred_count} وفشل {$job->failed_count}.";
        } else {
            $job->status = ImageTransferJob::STATUS_COMPLETED;
            $job->error_message = null;
        }

        $job->finished_at = now();
        $this->persistProgress($job);
        $this->trySyncJobToDatabase($job);

        if ($job->transferred_count > 0) {
            try {
                $this->sendCompletionNotification($job);
            } catch (\Throwable $e) {
                Log::warning('Image transfer completion notification failed', [
                    'job' => $job->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->cleanupStaging($job);
        $this->progress->forget($job);
    }

    protected function persistProgress(ImageTransferJob $job): void
    {
        $this->progress->write(
            $job,
            $job->manifest ?? [],
            (string) $job->status,
            $job->error_message,
        );
    }

    protected function trySyncJobToDatabase(ImageTransferJob $job): void
    {
        try {
            $job->save();
        } catch (\Throwable $e) {
            if ($this->isDatabaseLock($e)) {
                Log::warning('Image transfer progress kept on disk; sqlite sync deferred', [
                    'job' => $job->uuid,
                    'status' => $job->status,
                ]);

                return;
            }

            throw $e;
        }
    }

    protected function tryDeleteExistingContainerImages(string $containerNumber): void
    {
        try {
            ContainerImage::query()
                ->where('container_number', $containerNumber)
                ->delete();
        } catch (\Throwable $e) {
            if ($this->isDatabaseLock($e)) {
                Log::warning('Deferred container image replace due to sqlite lock', [
                    'container' => $containerNumber,
                ]);

                return;
            }

            throw $e;
        }
    }

    protected function freshWithProgress(int $jobId): ?ImageTransferJob
    {
        $job = ImageTransferJob::query()->find($jobId);

        if ($job === null) {
            return null;
        }

        return $this->progress->applyToJob($job);
    }

    protected function isDatabaseLock(\Throwable $e): bool
    {
        if ($e instanceof QueryException || $e instanceof PDOException) {
            return $this->messageIsDatabaseLock($e->getMessage());
        }

        return $this->messageIsDatabaseLock($e->getMessage());
    }

    protected function messageIsDatabaseLock(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'database is locked')
            || str_contains($message, 'sqlite_busy')
            || str_contains($message, 'error: 5');
    }

    protected function sendCompletionNotification(ImageTransferJob $job): void
    {
        if ($job->type === ImageTransferJob::TYPE_CONTAINER_ZIP && $job->container_number) {
            $this->notifications->notifyContainerImagesAdded(
                (string) $job->container_number,
                $job->transferred_count,
                $this->containers->dealersForContainer((string) $job->container_number),
                $job->user()->first(),
            );

            return;
        }

        if (! in_array($job->type, [
            ImageTransferJob::TYPE_VEHICLE_ZIP,
            ImageTransferJob::TYPE_VEHICLE_IMAGES,
        ], true) || ! $job->vehicle_id) {
            return;
        }

        $vehicle = Vehicle::query()->find($job->vehicle_id);

        if ($vehicle === null) {
            return;
        }

        $this->notifications->notifyVehicleImagesAdded(
            $vehicle,
            $job->transferred_count,
            (string) ($job->stage ?? 'terminal'),
            $job->user()->first(),
        );
    }

    protected function cleanupStaging(ImageTransferJob $job): void
    {
        if ($job->staging_dir === null || $job->staging_dir === '') {
            return;
        }

        $absolute = storage_path('app/'.$job->staging_dir);

        if (is_dir($absolute)) {
            File::deleteDirectory($absolute);
        }

        // storeAs() uses the local disk rooted at storage/app/private
        $privateAbsolute = storage_path('app/private/'.$job->staging_dir);

        if (is_dir($privateAbsolute)) {
            File::deleteDirectory($privateAbsolute);
        }
    }
}
