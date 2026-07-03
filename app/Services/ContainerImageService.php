<?php

namespace App\Services;

use App\Models\ContainerImage;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ContainerImageService
{
    public function __construct(
        protected CloudinaryService $cloudinary,
    ) {}

    public function normalizeContainerNumber(string $container): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($container)) ?? '');
    }

    /**
     * @param  list<string|null>  $alternateRefs
     * @return array{images: list<array<string, mixed>>, byVin: array<string, list<string>>, unmatched: list<string>, meta: array<string, mixed>}
     */
    public function payloadForContainer(string $container, array $alternateRefs = []): array
    {
        return $this->payloadForContainerKeys(array_merge([$container], $alternateRefs));
    }

    /**
     * @param  list<string|null>  $refs
     * @return array{images: list<array<string, mixed>>, byVin: array<string, list<string>>, unmatched: list<string>, meta: array<string, mixed>}
     */
    public function payloadForContainerKeys(array $refs): array
    {
        foreach ($this->uniqueLookupKeys($refs) as $number) {
            $records = ContainerImage::query()
                ->where('container_number', $number)
                ->orderBy('id')
                ->get();

            if ($records->isNotEmpty()) {
                return $this->buildPayloadFromRecords($records);
            }
        }

        return $this->emptyPayload();
    }

    /**
     * @param  list<string|null>  $refs
     * @return list<string>
     */
    protected function uniqueLookupKeys(array $refs): array
    {
        $keys = [];

        foreach ($refs as $ref) {
            if ($ref === null || $ref === '') {
                continue;
            }

            $normalized = $this->normalizeContainerNumber((string) $ref);

            if ($normalized !== '') {
                $keys[] = $normalized;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  list<array{name?: string, vin?: ?string, lot?: ?string}>  $metadata
     * @return array{images: list<array<string, mixed>>, byVin: array<string, list<string>>, unmatched: list<string>, meta: array<string, mixed>, uploaded: int, failed: list<array{index: int, name: string, error: string}>}
     */
    public function uploadBatch(string $container, array $files, array $metadata, bool $replace = true): array
    {
        if (! $this->cloudinary->isConfigured()) {
            throw new \RuntimeException('Cloudinary is not configured.');
        }

        $number = $this->normalizeContainerNumber($container);

        if ($number === '') {
            throw new \RuntimeException('Container reference is required.');
        }

        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($files === []) {
            throw new \RuntimeException(
                'No image files received by the server. Ensure the browser sends multipart/form-data with images[].',
            );
        }

        if ($replace) {
            ContainerImage::query()->where('container_number', $number)->delete();
        }

        $config = $this->cloudinary->resolveConfig();
        $folder = rtrim((string) ($config['folder'] ?? 'vinstack/containers'), '/').'/'.$number;
        $failed = [];
        $created = [];

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                $meta = $metadata[$index] ?? [];
                $failed[] = [
                    'index' => $index,
                    'name' => (string) ($meta['name'] ?? "image-{$index}"),
                    'error' => 'File was not received as a valid upload.',
                ];

                continue;
            }

            if (! $file->isValid()) {
                $meta = $metadata[$index] ?? [];
                $failed[] = [
                    'index' => $index,
                    'name' => (string) ($meta['name'] ?? $file->getClientOriginalName()),
                    'error' => 'Upload error: '.$file->getErrorMessage(),
                ];

                continue;
            }

            $meta = $metadata[$index] ?? [];
            $name = (string) ($meta['name'] ?? $file->getClientOriginalName());
            $vin = $this->normalizeVin($meta['vin'] ?? null);
            $vehicleId = $this->resolveVehicleId($vin);

            try {
                $upload = $this->cloudinary->upload($file, [
                    'folder' => $folder,
                    'public_id' => $this->publicIdFromFilename($name, $index),
                ]);

                $record = ContainerImage::query()->create([
                    'container_number' => $number,
                    'vehicle_id' => $vehicleId,
                    'vin' => $vin,
                    'original_name' => $name,
                    'cloudinary_url' => $upload['url'],
                    'public_id' => $upload['public_id'],
                    'uploaded_at' => now(),
                ]);

                $created[] = $record;
            } catch (\Throwable $e) {
                Log::warning('Container image Cloudinary upload failed', [
                    'container' => $number,
                    'index' => $index,
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);

                $failed[] = [
                    'index' => $index,
                    'name' => $name,
                    'error' => $e->getMessage(),
                ];
            } finally {
                $this->discardUploadedFile($file);
            }
        }

        if ($created === [] && $failed !== []) {
            Log::warning('Container image batch uploaded zero files', [
                'container' => $number,
                'file_count' => count($files),
                'failed' => $failed,
            ]);
        }

        $payload = $this->payloadForContainer($number);
        $payload['uploaded'] = count($created);
        $payload['failed'] = $failed;

        return $payload;
    }

    /**
     * @return array{payload: array<string, mixed>, cloudinary_warning: ?string}
     */
    public function delete(string $container, ContainerImage $image): array
    {
        $number = $this->normalizeContainerNumber($container);

        if ($number === '' || $image->container_number !== $number) {
            abort(404);
        }

        $cloudinaryWarning = null;

        if (filled($image->public_id)) {
            try {
                $this->cloudinary->destroy($image->public_id);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed for container image', [
                    'image_id' => $image->id,
                    'container' => $number,
                    'public_id' => $image->public_id,
                    'error' => $e->getMessage(),
                ]);

                $cloudinaryWarning = 'Image removed from container gallery; Cloudinary delete failed.';
            }
        }

        $image->delete();

        return [
            'payload' => $this->payloadForContainer($number),
            'cloudinary_warning' => $cloudinaryWarning,
        ];
    }

    /**
     * @param  Collection<int, ContainerImage>  $records
     * @return array{images: list<array<string, mixed>>, byVin: array<string, list<string>>, unmatched: list<string>, meta: array<string, mixed>}
     */
    protected function buildPayloadFromRecords(Collection $records): array
    {
        $images = [];
        $byVin = [];
        $unmatched = [];

        foreach ($records as $record) {
            $url = $record->cloudinary_url;
            $vin = $this->normalizeVin($record->vin);

            $images[] = [
                'id' => $record->id,
                'name' => $record->original_name,
                'url' => $url,
                'vin' => $vin ?: null,
                'public_id' => $record->public_id,
                'source' => 'cloudinary',
            ];

            if ($vin) {
                $byVin[$vin] ??= [];
                $byVin[$vin][] = $url;
            } else {
                $unmatched[] = $url;
            }
        }

        return [
            'images' => $images,
            'byVin' => $byVin,
            'unmatched' => $unmatched,
            'meta' => [
                'count' => count($images),
                'matched' => count($byVin),
                'unmatched' => count($unmatched),
                'storage' => 'cloudinary',
            ],
        ];
    }

    /**
     * @return array{images: list<array<string, mixed>>, byVin: array<string, list<string>>, unmatched: list<string>, meta: array<string, mixed>}
     */
    protected function emptyPayload(): array
    {
        return [
            'images' => [],
            'byVin' => [],
            'unmatched' => [],
            'meta' => [
                'count' => 0,
                'matched' => 0,
                'unmatched' => 0,
                'storage' => 'cloudinary',
            ],
        ];
    }

    protected function normalizeVin(?string $vin): ?string
    {
        if ($vin === null || trim($vin) === '') {
            return null;
        }

        return strtoupper(trim($vin));
    }

    protected function resolveVehicleId(?string $vin): ?int
    {
        if ($vin === null) {
            return null;
        }

        return Vehicle::query()->where('vin', $vin)->value('id');
    }

    protected function publicIdFromFilename(string $name, int $index): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base) ?? 'image';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'image';
        }

        return strtolower($slug).'-'.($index + 1);
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
