<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleUploadedImage;
use App\Support\VehicleGalleryMerger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleUploadedImageService
{
    public const MAX_FILES_PER_REQUEST = 20;

    public const MAX_FILE_KILOBYTES = 10240;

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array<string, mixed>>
     */
    public function storeMany(Vehicle $vehicle, string $stage, array $files, User $user): array
    {
        if (! VehicleUploadedImage::isValidStage($stage)) {
            abort(422, 'Invalid image stage.');
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
    public function storeOne(Vehicle $vehicle, string $stage, UploadedFile $file, User $user): array
    {
        $directory = "vehicle-images/{$vehicle->id}";
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');

        $image = VehicleUploadedImage::query()->create([
            'vehicle_id' => $vehicle->id,
            'stage' => $stage,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $user->id,
        ]);

        return $this->formatImage($image);
    }

    public function delete(Vehicle $vehicle, VehicleUploadedImage $image): void
    {
        if ($image->vehicle_id !== $vehicle->id) {
            abort(404);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForVehicle(Vehicle $vehicle): array
    {
        return $vehicle->uploadedImages()
            ->orderBy('stage')
            ->orderBy('id')
            ->get()
            ->map(fn (VehicleUploadedImage $image) => $this->formatImage($image))
            ->values()
            ->all();
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
            'source' => 'local',
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
        $vinstackStages = VehicleGalleryMerger::resolveVinstackStages($raw, $vehicle);
        $imagesByStage = VehicleGalleryMerger::merge($vinstackStages, $vehicle);
        $images = VehicleGalleryMerger::flatten($imagesByStage, $vehicle, $raw);

        $thumbnail = Arr::get($raw, 'thumbnail_url');
        if (! is_string($thumbnail) || $thumbnail === '' || str_contains($thumbnail, 'no_photo.png')) {
            $thumbnail = $images[0] ?? null;
        }

        $data = $vehicle->toArray();
        $data['images'] = $images;
        $data['images_by_stage'] = $imagesByStage;
        $data['thumbnail_url'] = $thumbnail;
        $data['uploaded_images'] = array_map(
            fn (VehicleUploadedImage $image) => $this->formatImage($image),
            $vehicle->uploadedImages->all(),
        );

        if (is_array($data['raw_data'] ?? null)) {
            $data['raw_data']['images'] = $images;
            $data['raw_data']['images_by_stage'] = $imagesByStage;

            if (is_string($thumbnail) && $thumbnail !== '') {
                $data['raw_data']['thumbnail_url'] = $thumbnail;
            }
        }

        return $data;
    }
}
