<?php

namespace App\Services;

use App\Jobs\ProcessImageTransferBatch;
use App\Models\ContainerImage;
use App\Models\ImageTransferJob;
use App\Models\VinstackSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImageTransferProcessor
{
    public function __construct(
        protected ContainerImageService $images,
        protected ContainerService $containers,
        protected DealerNotificationService $notifications,
    ) {}

    /**
     * Process one batch for a transfer job. Returns true when more work remains.
     */
    public function processBatch(int $jobId): bool
    {
        $job = ImageTransferJob::query()->lockForUpdate()->find($jobId);

        if ($job === null || $job->isFinished()) {
            return false;
        }

        if ($job->status === ImageTransferJob::STATUS_QUEUED) {
            $job->status = ImageTransferJob::STATUS_PROCESSING;
            $job->started_at = $job->started_at ?? now();

            if ($job->replace_existing) {
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
                $this->images->uploadStagedImage((string) $job->container_number, $item);
                $manifest[$offset]['status'] = 'done';
                $manifest[$offset]['error'] = null;
                $job->transferred_count++;
            } catch (\Throwable $e) {
                Log::warning('Image transfer batch item failed', [
                    'job' => $job->uuid,
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

        if ($job->hasPendingManifestItems()) {
            $job->save();

            return true;
        }

        $this->finalizeJob($job);

        return false;
    }

    public function processPendingJobs(int $limit = 5): int
    {
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

    protected function finalizeJob(ImageTransferJob $job): void
    {
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
            $this->notifications->notifyContainerImagesAdded(
                (string) $job->container_number,
                $job->transferred_count,
                $this->containers->dealersForContainer((string) $job->container_number),
                $job->user()->first(),
            );
        }

        $this->cleanupStaging($job);
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
