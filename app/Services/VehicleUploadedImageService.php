<?php



namespace App\Services;



use App\Enums\VehicleSource;
use App\Models\User;

use App\Models\Vehicle;

use App\Models\VehicleUploadedImage;

use App\Support\UploadLimits;
use App\Support\VehicleGalleryMerger;

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
    public function storeFromPath(Vehicle $vehicle, string $stage, string $path, string $originalName, User $user): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('upload_file_unreadable');
        }

        $file = new UploadedFile($path, $originalName, null, null, true);

        return $this->storeOne($vehicle, $stage, $file, $user);
    }



    /**

     * @return array<string, mixed>

     */

    public function storeOne(Vehicle $vehicle, string $stage, UploadedFile $file, User $user): array

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

            $this->discardUploadedFile($file);

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
        if ($image->vehicle_id !== $vehicle->id) {
            abort(404);
        }

        $cloudinaryWarning = null;

        if (filled($image->public_id)) {
            try {
                $this->cloudinary->destroy($image->public_id);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed for vehicle uploaded image', [
                    'image_id' => $image->id,
                    'public_id' => $image->public_id,
                    'error' => $e->getMessage(),
                ]);

                $cloudinaryWarning = 'Image removed from gallery; Cloudinary delete failed.';
            }
        }

        if (filled($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        return ['cloudinary_warning' => $cloudinaryWarning];
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

            'source' => $image->isCloudinary() ? 'cloudinary' : 'local',

            'public_id' => $image->public_id,

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

        $data['uploaded_images'] = array_map(

            fn (VehicleUploadedImage $image) => $this->formatImage($image),

            $vehicle->uploadedImages->all(),

        );



        if (is_array($data['raw_data'] ?? null)) {

            $data['raw_data'] = VehicleRawDataLocations::sanitizeForList($data['raw_data']);

            $data['raw_data']['images'] = $images;

            $data['raw_data']['images_by_stage'] = $imagesByStage;



            if (is_string($thumbnail) && $thumbnail !== '') {

                $data['raw_data']['thumbnail_url'] = $thumbnail;

            }

        }



        return $data;

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

