<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Services\VehicleUploadedImageService;
use App\Support\VehicleGalleryMerger;
use Illuminate\Support\Arr;
use RuntimeException;

class VehicleDetailService
{
    public function __construct(
        protected VinstackService $vinstack,
        protected VehicleUploadedImageService $uploadedImages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Vehicle $vehicle, bool $includeAssignment = false): array
    {
        $vehicle->loadMissing(['activeAssignment.dealer']);

        $local = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $fresh = $this->fetchVinstackData($vehicle);
        $merged = array_merge($local, $fresh);

        $vehicle->loadMissing('uploadedImages');

        $vinstackStages = VehicleGalleryMerger::resolveVinstackStages($merged, $vehicle);
        $imagesByStage = VehicleGalleryMerger::merge($vinstackStages, $vehicle);
        $images = VehicleGalleryMerger::flatten($imagesByStage, $vehicle, $merged);

        $payload = [
            'id' => $vehicle->id,
            'vin' => $this->string($merged, 'vin') ?: $vehicle->vin,
            'title' => $this->buildTitle($merged, $vehicle),
            'status' => $this->string($merged, 'status'),
            'local_status' => $vehicle->status?->value ?? (string) $vehicle->status,
            'images' => $images,
            'images_by_stage' => $imagesByStage,
            'uploaded_images' => $this->uploadedImages->listForVehicle($vehicle),
            'thumbnail_url' => $this->thumbnailUrl($merged),
            'vinstack_fresh' => $fresh !== [],
            'sections' => $this->buildSections($merged),
            'invoices' => $this->normalizeInvoices($merged),
            'documents' => $this->normalizeDocuments($merged),
        ];

        if ($includeAssignment && $vehicle->activeAssignment) {
            $payload['assignment'] = [
                'dealer_name' => $vehicle->activeAssignment->dealer?->company_name,
                'assigned_at' => $vehicle->activeAssignment->assigned_at?->toIso8601String(),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchVinstackData(Vehicle $vehicle): array
    {
        $vin = trim((string) $vehicle->vin);

        if ($vin === '') {
            return [];
        }

        try {
            return $this->vinstack->auto($vin);
        } catch (RuntimeException) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return list<array{key: string, title: string, fields: list<array{key: string, label: string, value: mixed, type: string}>}>
     */
    protected function buildSections(array $merged): array
    {
        $sections = [
            [
                'key' => 'vehicle_information',
                'title' => 'Vehicle information',
                'fields' => $this->fields($merged, [
                    'vin' => 'VIN',
                    'year' => 'Year',
                    'make' => 'Make',
                    'model' => 'Model',
                    'vehicle_type' => 'Vehicle type',
                    'fuel_type' => 'Fuel type',
                    'color' => 'Color',
                    'weight' => 'Weight',
                ]),
            ],
            [
                'key' => 'sale_information',
                'title' => 'Sale information',
                'fields' => $this->fields($merged, [
                    'auction' => 'Auction',
                    'buyer' => 'Buyer',
                    'lot' => 'Lot',
                    'purchase_date' => 'Purchase date',
                    'value' => 'Value',
                ], ['purchase_date' => 'date', 'value' => 'money']),
            ],
            [
                'key' => 'dates',
                'title' => 'Dates',
                'fields' => $this->fields($merged, [
                    'purchase_date' => 'Purchase date',
                    'arrived_terminal_date' => 'Arrived terminal',
                    'left_terminal' => 'Left terminal',
                    'title_received' => 'Title received',
                ], [
                    'purchase_date' => 'date',
                    'arrived_terminal_date' => 'date',
                    'left_terminal' => 'date',
                    'title_received' => 'date',
                ]),
            ],
            [
                'key' => 'shipping_information',
                'title' => 'Shipping information',
                'fields' => $this->fields($merged, [
                    'shipping_method' => 'Shipping method',
                    'delivery_type' => 'Delivery type',
                    'container_number' => 'Container number',
                    'booking_number' => 'Booking number',
                    'loading_point' => 'Loading point',
                    'destination' => 'Destination',
                    'location' => 'Location',
                ]),
            ],
            [
                'key' => 'other',
                'title' => 'Other',
                'fields' => $this->fields($merged, [
                    'status' => 'Status',
                    'title_status' => 'Title status',
                    'title_type' => 'Title type',
                    'keys' => 'Keys',
                ]),
            ],
        ];

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $merged
     * @param  array<string, string>  $map
     * @param  array<string, string>  $types
     * @return list<array{key: string, label: string, value: mixed, type: string}>
     */
    protected function fields(array $merged, array $map, array $types = []): array
    {
        $fields = [];

        foreach ($map as $key => $label) {
            $fields[] = [
                'key' => $key,
                'label' => $label,
                'value' => Arr::get($merged, $key),
                'type' => $types[$key] ?? 'text',
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    protected function buildTitle(array $merged, Vehicle $vehicle): string
    {
        $parts = array_filter([
            Arr::get($merged, 'year') ?? $vehicle->year,
            Arr::get($merged, 'make') ?? $vehicle->make,
            Arr::get($merged, 'model') ?? $vehicle->model,
        ], fn ($part) => $part !== null && $part !== '');

        return implode(' ', array_map('strval', $parts));
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    protected function thumbnailUrl(array $merged): ?string
    {
        $url = Arr::get($merged, 'thumbnail_url');

        if (! is_string($url) || $url === '' || str_contains($url, 'no_photo.png')) {
            return null;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return list<array<string, mixed>>
     */
    protected function normalizeInvoices(array $merged): array
    {
        $invoices = Arr::get($merged, 'invoices', []);

        if (! is_array($invoices)) {
            return [];
        }

        return array_values(array_filter($invoices, fn ($item) => is_array($item)));
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return list<array<string, mixed>>
     */
    protected function normalizeDocuments(array $merged): array
    {
        $documents = Arr::get($merged, 'documents', []);

        if (! is_array($documents)) {
            return [];
        }

        return array_values(array_filter($documents, fn ($item) => is_array($item)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function string(array $data, string $key): ?string
    {
        $value = Arr::get($data, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value) ?: null;
    }
}
