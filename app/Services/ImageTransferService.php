<?php

namespace App\Services;

use App\Jobs\ProcessImageTransferBatch;
use App\Models\ImageTransferJob;
use App\Models\User;
use App\Models\VinstackSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImageTransferService
{
    public function __construct(
        protected ContainerZipUploadService $zipUploads,
        protected ContainerService $containers,
        protected ContainerImageService $images,
        protected CloudinaryService $cloudinary,
    ) {}

    public function asyncEnabled(): bool
    {
        return (bool) (VinstackSetting::current()->image_transfer_async_enabled ?? true);
    }

    public function createContainerZipJob(
        string $containerRef,
        UploadedFile $zip,
        ?User $user = null,
        bool $replace = true,
    ): ImageTransferJob {
        if (! $this->cloudinary->isConfigured()) {
            throw new RuntimeException('Cloudinary is not configured.');
        }

        $number = $this->images->normalizeContainerNumber($containerRef);

        if ($number === '') {
            throw new RuntimeException('Container reference is required.');
        }

        $uuid = (string) Str::uuid();
        $relativeDir = 'image-transfers/'.$uuid;
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            throw new RuntimeException('Could not create staging directory.');
        }

        $zip->storeAs($relativeDir, 'archive.zip');

        $zipPath = $zip->getRealPath();

        if (! is_string($zipPath) || $zipPath === '') {
            $zipPath = storage_path('app/'.$relativeDir.'/archive.zip');
        }

        $entries = $this->zipUploads->extractToStagingDirectory($zipPath, $absoluteDir.'/images');

        $detail = $this->containers->vehiclesForContainer($containerRef);
        $vehicles = $detail['vehicles'] ?? [];
        $manifest = [];

        foreach ($entries as $index => $entry) {
            $match = $this->zipUploads->matchImageToVehicle($entry['name'], $vehicles, $index);
            $manifest[] = [
                'index' => $index,
                'name' => $entry['name'],
                'path' => $entry['path'],
                'vin' => $match['vin'],
                'lot' => $match['lot'],
                'status' => 'pending',
                'error' => null,
            ];
        }

        return DB::transaction(function () use ($uuid, $number, $user, $replace, $relativeDir, $manifest) {
            $job = ImageTransferJob::query()->create([
                'uuid' => $uuid,
                'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
                'status' => ImageTransferJob::STATUS_QUEUED,
                'container_number' => $number,
                'user_id' => $user?->id,
                'replace_existing' => $replace,
                'total_images' => count($manifest),
                'transferred_count' => 0,
                'failed_count' => 0,
                'staging_dir' => $relativeDir,
                'manifest' => $manifest,
            ]);

            ProcessImageTransferBatch::dispatch($job->id);

            return $job;
        });
    }
}
