<?php

namespace App\Services;

use App\Models\ImageTransferJob;
use Illuminate\Support\Facades\File;

/**
 * File-backed transfer progress so batches do not depend on SQLite mid-upload.
 */
class ImageTransferProgressStore
{
    /**
     * @return array{
     *     status: string,
     *     transferred_count: int,
     *     failed_count: int,
     *     manifest: list<array<string, mixed>>,
     *     error_message: ?string,
     *     updated_at: string
     * }|null
     */
    public function read(ImageTransferJob $job): ?array
    {
        $path = $this->pathFor($job);

        if ($path === null || ! File::exists($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) File::get($path), true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($decoded) || ! isset($decoded['manifest']) || ! is_array($decoded['manifest'])) {
            return null;
        }

        return [
            'status' => (string) ($decoded['status'] ?? $job->status),
            'transferred_count' => (int) ($decoded['transferred_count'] ?? 0),
            'failed_count' => (int) ($decoded['failed_count'] ?? 0),
            'manifest' => array_values($decoded['manifest']),
            'error_message' => isset($decoded['error_message']) ? (string) $decoded['error_message'] : null,
            'updated_at' => (string) ($decoded['updated_at'] ?? now()->toIso8601String()),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     */
    public function write(ImageTransferJob $job, array $manifest, string $status, ?string $errorMessage = null): void
    {
        $path = $this->pathFor($job);

        if ($path === null) {
            return;
        }

        $dir = dirname($path);

        try {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        } catch (\Throwable) {
            $path = storage_path('app/image-transfers/progress/'.$job->uuid.'.json');
            $dir = dirname($path);

            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }

        $transferred = 0;
        $failed = 0;

        foreach ($manifest as $item) {
            $itemStatus = $item['status'] ?? '';

            if ($itemStatus === 'done') {
                $transferred++;
            } elseif ($itemStatus === 'failed') {
                $failed++;
            }
        }

        File::put($path, json_encode([
            'uuid' => $job->uuid,
            'status' => $status,
            'transferred_count' => $transferred,
            'failed_count' => $failed,
            'manifest' => array_values($manifest),
            'error_message' => $errorMessage,
            'updated_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function applyToJob(ImageTransferJob $job): ImageTransferJob
    {
        $progress = $this->read($job);

        if ($progress === null) {
            return $job;
        }

        $job->manifest = $progress['manifest'];
        $job->transferred_count = $progress['transferred_count'];
        $job->failed_count = $progress['failed_count'];
        $job->status = $progress['status'];
        $job->error_message = $progress['error_message'];
        $job->recalculateCountersFromManifest();

        return $job;
    }

    public function forget(ImageTransferJob $job): void
    {
        $path = $this->pathFor($job);

        if ($path !== null && File::exists($path)) {
            File::delete($path);
        }
    }

    protected function pathFor(ImageTransferJob $job): ?string
    {
        if ($job->staging_dir === null || $job->staging_dir === '') {
            return storage_path('app/image-transfers/progress/'.$job->uuid.'.json');
        }

        $staging = (string) $job->staging_dir;

        // Absolute paths (tests / legacy rows)
        if (
            str_starts_with($staging, '/')
            || str_starts_with($staging, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $staging) === 1
        ) {
            return rtrim($staging, '/\\').DIRECTORY_SEPARATOR.'progress.json';
        }

        return storage_path('app/'.$staging.'/progress.json');
    }
}
