<?php

namespace App\Services;

use App\Enums\VehicleSource;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\UploadLimits;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleGalleryStore;
use App\Support\VehicleRawDataLocations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleUploadedImageService
{
    public const MAX_FILES_PER_REQUEST = 20;

    public const MAX_FILE_KILOBYTES = 10240;

    public function __construct(
        protected CloudinaryService $cloudinary,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    public function storeMany(Vehicle $vehicle, string $stage, array $files, User $user): array
    {
        UploadLimits::extendExecutionTime();

        if (! VehicleUploadedImage::isValidStage($stage)) {
            abort(422, 'Invalid image stage.');
        }

        if (! $this->cloudinary->isConfigured()) {
            abort(422, 'Cloudinary is not configured.');
        }

        $created = [];

        foreach ($files as $file) {
            $created[] = $this->storeOne($vehicle, $stage, $file, $user);
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    public function storeFromPath(
        Vehicle $vehicle,
        string $stage,
        string $path,
        string $originalName,
        User $user,
        bool $discardAfterUpload = true,
    ): array {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('upload_file_unreadable');
        }

        $file = new UploadedFile($path, $originalName, null, null, true);

        return $this->storeOne($vehicle, $stage, $file, $user, $discardAfterUpload);
    }

    /**
     * @return array<string, mixed>
     */
    public function storeOne(Vehicle $vehicle, string $stage, UploadedFile $file, User $user, bool $discardAfterUpload = true): array
    {
        $config = $this->cloudinary->resolveConfig();
        $baseFolder = rtrim((string) ($config['folder'] ?? 'vinstack'), '/');
        $folder = "{$baseFolder}/vehicles/{$vehicle->id}/{$stage}";
        $publicId = Str::uuid()->toString();

        try {
            $upload = $this->cloudinary->upload($file, [
                'folder' => $folder,
                'public_id' => $publicId,
            ]);
        } finally {
            if ($discardAfterUpload) {
                $this->discardUploadedFile($file);
            }
        }

        // Phase 1: new uploads go to the per-vehicle gallery file (no SQLite row).
        if (VehicleGalleryStore::isEnabled()) {
            return VehicleGalleryStore::append($vehicle, [
                'stage' => $stage,
                'url' => $upload['url'],
                'public_id' => $upload['public_id'],
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
                'source' => 'cloudinary',
            ]);
        }

        $image = VehicleUploadedImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'stage' => $stage,
            'path' => null,
            'cloudinary_url' => $upload['url'],
            'public_id' => $upload['public_id'],
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $user->id,
        ]);

        return $this->formatImage($image);
    }

    /**
     * @return array{cloudinary_warning: ?string}
     */
    public function delete(Vehicle $vehicle, VehicleUploadedImage $image): array
    {
        return $this->deleteById($vehicle, (string) $image->id);
    }

    /**
     * Delete a legacy DB row or a file-gallery image by id.
     *
     * @return array{cloudinary_warning: ?string}
     */
    public function deleteById(Vehicle $vehicle, string $imageId): array
    {
        if (VehicleGalleryStore::isFileGalleryId($imageId)) {
            $row = VehicleGalleryStore::find($vehicle, $imageId);

            if ($row === null) {
                abort(404);
            }

            $cloudinaryWarning = $this->destroyCloudinaryPublicId(
                isset($row['public_id']) ? (string) $row['public_id'] : null,
                $imageId,
            );

            VehicleGalleryStore::remove($vehicle, $imageId);

            return ['cloudinary_warning' => $cloudinaryWarning];
        }

        $image = VehicleUploadedImage::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereKey($imageId)
            ->first();

        if ($image === null) {
            abort(404);
        }

        $cloudinaryWarning = $this->destroyCloudinaryPublicId(
            filled($image->public_id) ? (string) $image->public_id : null,
            (string) $image->id,
        );

        if (filled($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        return ['cloudinary_warning' => $cloudinaryWarning];
    }

    /**
     * Dual-read: legacy DB rows + new file-gallery entries.
     *
     * @return list<array<string, mixed>>
     */
    public function listForVehicle(Vehicle $vehicle): array
    {
        $legacy = $vehicle->uploadedImages()
            ->orderBy('stage')
            ->orderBy('id')
            ->get()
            ->map(fn (VehicleUploadedImage $image) => $this->formatImage($image))
            ->values()
            ->all();

        $file = VehicleGalleryStore::isEnabled()
            ? VehicleGalleryStore::formatted($vehicle)
            : [];

        return array_values([...$legacy, ...$file]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatImage(VehicleUploadedImage $image): array
    {
        return [
            'id' => $image->id,
            'stage' => $image->stage,
            'url' => $image->publicUrl(),
            'original_name' => $image->original_name,
            'uploaded_at' => $image->created_at?->toIso8601String(),
            'source' => $image->isCloudinary() ? 'cloudinary' : 'local',
            'public_id' => $image->public_id,
            'storage' => 'database',
        ];
    }

    /**
     * Enrich a vehicle model/array for list responses with merged gallery fields.
     *
     * @return array<string, mixed>
     */
    public function enrichListVehicle(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('uploadedImages');

        $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $imagesByStage = VehicleGalleryMerger::resolveDisplayStages($raw, $vehicle);
        $images = VehicleGalleryMerger::flatten($imagesByStage, $vehicle, $raw);

        $thumbnail = Arr::get($raw, 'thumbnail_url');

        if (! is_string($thumbnail) || $thumbnail === '' || str_contains($thumbnail, 'no_photo.png')) {
            $thumbnail = $images[0] ?? null;
        }

        $data = $vehicle->toArray();
        $source = $vehicle->source ?? VehicleSource::Vinstack;
        $data['source'] = $source->value;
        $data['source_label'] = $source->label();
        $data['images'] = $images;
        $data['images_by_stage'] = $imagesByStage;
        $data['thumbnail_url'] = $thumbnail;
        $data['eta'] = $vehicle->eta;
        $data['uploaded_images'] = $this->listForVehicle($vehicle);

        if (is_array($data['raw_data'] ?? null)) {
            if (! isset($data['raw_data']['eta']) && $vehicle->eta) {
                $data['raw_data']['eta'] = $vehicle->eta;
            }

            $data['raw_data'] = VehicleRawDataLocations::sanitizeForList($data['raw_data']);
            $data['raw_data']['images'] = $images;
            $data['raw_data']['images_by_stage'] = $imagesByStage;

            if (is_string($thumbnail) && $thumbnail !== '') {
                $data['raw_data']['thumbnail_url'] = $thumbnail;
            }
        }

        return $data;
    }

    protected function destroyCloudinaryPublicId(?string $publicId, string $imageId): ?string
    {
        if ($publicId === null || $publicId === '') {
            return null;
        }

        try {
            $this->cloudinary->destroy($publicId);
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed for vehicle uploaded image', [
                'image_id' => $imageId,
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return 'Image removed from gallery; Cloudinary delete failed.';
        }

        return null;
    }

    protected function discardUploadedFile(UploadedFile $file): void
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return;
        }

        @unlink($path);
    }
}
