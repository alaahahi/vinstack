<?php

namespace App\Services;

use App\Jobs\ProcessImageTransferBatch;
use App\Models\ContainerImage;
use App\Models\ImageTransferJob;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VinstackSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImageTransferProcessor
{
    public function __construct(
        protected ContainerImageService $images,
        protected ContainerService $containers,
        protected DealerNotificationService $notifications,
        protected VehicleUploadedImageService $vehicleImages,
        protected ImageTransferHealthService $health,
    ) {}

    /**
     * Process one batch for a transfer job. Returns true when more work remains.
     */
    public function processBatch(int $jobId): bool
    {
        $more = DB::transaction(function () use ($jobId) {
            $job = ImageTransferJob::query()->lockForUpdate()->find($jobId);

            if ($job === null || $job->isFinished()) {
                return false;
            }

            if ($job->status === ImageTransferJob::STATUS_CANCELLED) {
                return false;
            }

            if ($job->status === ImageTransferJob::STATUS_QUEUED) {
                $job->status = ImageTransferJob::STATUS_PROCESSING;
                $job->started_at = $job->started_at ?? now();

                if ($job->type === ImageTransferJob::TYPE_CONTAINER_ZIP && $job->replace_existing) {
                    ContainerImage::query()
                        ->where('container_number', $job->container_number)
                        ->delete();
                }

                $job->save();
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
                    $job->transferred_count++;
                } catch (\Throwable $e) {
                    Log::warning('Image transfer batch item failed', [
                        'job' => $job->uuid,
                        'type' => $job->type,
                        'name' => $item['name'] ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    $manifest[$offset]['status'] = 'failed';
                    $manifest[$offset]['error'] = $e->getMessage();
                    $job->failed_count++;
                }

                $processed++;
            }

            $job->manifest = array_values($manifest);
            $job->recalculateCountersFromManifest();

            if ($job->hasPendingManifestItems()) {
                $job->save();

                return true;
            }

            $this->finalizeJob($job);

            return false;
        });

        $this->health->markBatchProcessed('processor', $jobId);

        return $more;
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

    /**
     * Force one batch now and re-dispatch if needed.
     */
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

        return $job->fresh() ?? $job;
    }

    public function cancel(ImageTransferJob $job): ImageTransferJob
    {
        return DB::transaction(function () use ($job) {
            $locked = ImageTransferJob::query()->lockForUpdate()->find($job->id);

            if ($locked === null || $locked->isFinished()) {
                return $locked ?? $job;
            }

            $locked->status = ImageTransferJob::STATUS_CANCELLED;
            $locked->error_message = $locked->error_message
                ?: 'تم إلغاء مهمة النقل بواسطة الإدارة.';
            $locked->finished_at = now();
            $locked->save();

            $this->cleanupStaging($locked);

            return $locked->fresh() ?? $locked;
        });
    }

    public function retryFailed(ImageTransferJob $job): ImageTransferJob
    {
        if (! in_array($job->status, [
            ImageTransferJob::STATUS_FAILED,
            ImageTransferJob::STATUS_PARTIAL,
        ], true)) {
            throw new \RuntimeException('لا يمكن إعادة محاولة هذه المهمة في حالتها الحالية.');
        }

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
        $job->save();

        $more = $this->processBatch($job->id);

        if ($more) {
            ProcessImageTransferBatch::dispatch($job->id);
        }

        return $job->fresh() ?? $job;
    }

    public function markFailedFromQueue(int $jobId, string $message): void
    {
        $job = ImageTransferJob::query()->find($jobId);

        if ($job === null || $job->isFinished()) {
            return;
        }

        $job->status = ImageTransferJob::STATUS_FAILED;
        $job->error_message = $message !== ''
            ? $message
            : 'توقفت مهمة النقل بعد فشل الطابور.';
        $job->finished_at = now();
        $job->save();
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
        $job->save();

        if ($job->transferred_count > 0) {
            $this->sendCompletionNotification($job);
        }

        $this->cleanupStaging($job);
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
    }
}
