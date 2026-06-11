<?php

namespace App\Console\Commands;

use App\Models\VehicleUploadedImage;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateImagesToCloudinary extends Command
{
    protected $signature = 'images:migrate-to-cloudinary
                            {--dry-run : List local images without uploading}
                            {--limit= : Maximum number of records to migrate}';

    protected $description = 'Upload locally stored vehicle images to Cloudinary';

    public function handle(CloudinaryService $cloudinary): int
    {
        if (! $cloudinary->isConfigured()) {
            $this->error('Cloudinary is not configured.');

            return self::FAILURE;
        }

        $query = VehicleUploadedImage::query()
            ->whereNull('cloudinary_url')
            ->whereNotNull('path')
            ->orderBy('id');

        $limit = $this->option('limit');

        if (is_numeric($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info('No local vehicle images to migrate.');

            return self::SUCCESS;
        }

        $this->info("Found {$records->count()} local vehicle image(s).");

        if ($this->option('dry-run')) {
            foreach ($records as $record) {
                $this->line("  #{$record->id} vehicle={$record->vehicle_id} stage={$record->stage} path={$record->path}");
            }

            return self::SUCCESS;
        }

        $config = $cloudinary->resolveConfig();
        $baseFolder = rtrim((string) ($config['folder'] ?? 'vinstack'), '/');
        $disk = Storage::disk('public');
        $migrated = 0;
        $failed = 0;

        foreach ($records as $record) {
            if (! $disk->exists($record->path)) {
                $this->warn("  Skipping #{$record->id}: file missing ({$record->path})");
                $failed++;

                continue;
            }

            $absolutePath = $disk->path($record->path);
            $folder = "{$baseFolder}/vehicles/{$record->vehicle_id}/{$record->stage}";

            try {
                $upload = $cloudinary->upload($absolutePath, [
                    'folder' => $folder,
                    'public_id' => Str::uuid()->toString(),
                ]);

                $localPath = $record->path;

                $record->update([
                    'cloudinary_url' => $upload['url'],
                    'public_id' => $upload['public_id'],
                    'path' => null,
                ]);

                $disk->delete($localPath);
                $migrated++;
                $this->line("  Migrated #{$record->id} → {$upload['public_id']}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  Failed #{$record->id}: {$e->getMessage()}");
                Log::warning('images:migrate-to-cloudinary failed', [
                    'image_id' => $record->id,
                    'path' => $record->path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Migrated: {$migrated}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
