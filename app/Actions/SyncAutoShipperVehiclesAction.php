<?php

namespace App\Actions;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\AutoshipperSetting;
use App\Models\Vehicle;
use App\Services\AutoShipperService;
use App\Services\DealerNotificationService;
use App\Services\VehicleStatusNotificationService;
use App\Support\VehicleEta;
use App\Support\VehicleGalleryMerger;
use App\Support\VehicleImageStages;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SyncAutoShipperVehiclesAction
{
    public function __construct(
        protected AutoShipperService $autoshipper,
        protected VehicleStatusNotificationService $statusNotifications,
        protected DealerNotificationService $dealerNotifications,
    ) {}

    /**
     * @return array{created: int, updated: int, total: int, skipped: int, restorable: list<array{id: int, vin: string|null}>}
     */
    public function execute(): array
    {
        $vehicles = $this->autoshipper->vehicles();
        $mediaByVehicle = $this->indexByVehicleId($this->autoshipper->media());
        $chargesByVehicle = $this->indexByVehicleId($this->autoshipper->vehicleCharges());

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $restorableById = [];

        foreach ($vehicles as $item) {
            if (! is_array($item)) {
                $skipped++;

                continue;
            }

            $externalId = $this->resolveExternalId($item);

            if ($externalId === '') {
                $skipped++;

                continue;
            }

            $media = $mediaByVehicle[$externalId] ?? [];
            $charges = $chargesByVehicle[$externalId] ?? [];
            $payload = $this->mapVehicle($item, $media, $charges);
            $vin = isset($payload['vin']) ? strtoupper(trim((string) $payload['vin'])) : null;
            if ($vin === '') {
                $vin = null;
            }

            $vehicle = Vehicle::withTrashed()
                ->where('autoshipper_id', $externalId)
                ->first();

            if ($vehicle) {
                if ($vehicle->trashed()) {
                    $this->trackRestorable($restorableById, $vehicle);
                    $skipped++;

                    continue;
                }

                if ($vehicle->source === VehicleSource::Manual) {
                    $skipped++;

                    continue;
                }

                if ($vehicle->source !== VehicleSource::AutoShipper) {
                    $skipped++;

                    continue;
                }

                $previousRaw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
                $merged = VehicleGalleryMerger::mergeSyncPayload($vehicle, $payload);
                $vehicle->update($merged);

                $statusChange = $this->statusNotifications->recordFromRawDataChange(
                    $vehicle,
                    $previousRaw,
                    is_array($merged['raw_data'] ?? null) ? $merged['raw_data'] : [],
                    'autoshipper_sync',
                );

                if ($statusChange !== null) {
                    $this->dealerNotifications->notifyVehicleUpdated(
                        $vehicle,
                        $statusChange->previous_status,
                        (string) $statusChange->new_status,
                        source: 'autoshipper_sync',
                    );
                }

                $updated++;

                continue;
            }

            if ($vin !== null) {
                $existingByVin = Vehicle::withTrashed()->where('vin', $vin)->first();

                if ($existingByVin) {
                    if ($existingByVin->trashed()) {
                        $this->trackRestorable($restorableById, $existingByVin);
                        $skipped++;

                        continue;
                    }

                    // Never overwrite Vinstack / Nujoom / Manual by VIN.
                    if ($existingByVin->source !== VehicleSource::AutoShipper) {
                        $skipped++;

                        continue;
                    }

                    if ($existingByVin->autoshipper_id && $existingByVin->autoshipper_id !== $externalId) {
                        $skipped++;

                        continue;
                    }

                    $previousRaw = is_array($existingByVin->raw_data) ? $existingByVin->raw_data : [];
                    $merged = VehicleGalleryMerger::mergeSyncPayload($existingByVin, [
                        ...$payload,
                        'autoshipper_id' => $externalId,
                    ]);
                    $existingByVin->update($merged);

                    $statusChange = $this->statusNotifications->recordFromRawDataChange(
                        $existingByVin,
                        $previousRaw,
                        is_array($merged['raw_data'] ?? null) ? $merged['raw_data'] : [],
                        'autoshipper_sync',
                    );

                    if ($statusChange !== null) {
                        $this->dealerNotifications->notifyVehicleUpdated(
                            $existingByVin,
                            $statusChange->previous_status,
                            (string) $statusChange->new_status,
                            source: 'autoshipper_sync',
                        );
                    }

                    $updated++;

                    continue;
                }
            }

            Vehicle::query()->create([
                ...$payload,
                'source' => VehicleSource::AutoShipper,
                'autoshipper_id' => $externalId,
                'vinstack_id' => null,
                'status' => VehicleStatus::Available,
            ]);
            $created++;
        }

        AutoshipperSetting::current()->update([
            'last_sync_at' => now(),
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($vehicles),
            'skipped' => $skipped,
            'restorable' => array_values($restorableById),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<array<string, mixed>>>
     */
    protected function indexByVehicleId(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $vehicleId = $this->resolveRelatedVehicleId($row);

            if ($vehicleId === '') {
                continue;
            }

            $indexed[$vehicleId][] = $row;
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $media
     * @param  list<array<string, mixed>>  $charges
     * @return array<string, mixed>
     */
    protected function mapVehicle(array $item, array $media, array $charges): array
    {
        $vin = $this->stringFrom($item, ['vin', 'VIN', 'chassis', 'chassisNumber']);
        if ($vin !== null) {
            $vin = strtoupper($vin);
        }

        $make = $this->stringFrom($item, ['manufacturer', 'make', 'brand']);
        $model = $this->stringFrom($item, ['model', 'carModel', 'vehicleModel']);
        $year = $this->intFrom($item, ['year', 'modelYear', 'model_year']);
        $price = $this->floatFrom($item, [
            'price',
            'value',
            'purchasePrice',
            'purchase_price',
            'auctionPrice',
            'estimated_retail_value',
        ]);

        $purchaseDate = VehicleEta::normalize(
            Arr::get($item, 'invoice_date')
                ?? Arr::get($item, 'purchase_date')
                ?? Arr::get($item, 'purchaseDate')
                ?? Arr::get($item, 'purchased_at')
        );

        $loadingDate = VehicleEta::normalize(
            Arr::get($item, 'picked_up_date')
                ?? Arr::get($item, 'pickedUpDate')
                ?? Arr::get($item, 'loading_date')
                ?? Arr::get($item, 'loaded_date')
                ?? Arr::get($item, 'date_pick')
        );

        $eta = VehicleEta::normalize(
            Arr::get($item, 'eta_date')
                ?? Arr::get($item, 'eta')
                ?? Arr::get($item, 'estimated_arrival')
                ?? Arr::get($item, 'ETA')
        );

        $deliveredDate = VehicleEta::normalize(
            Arr::get($item, 'delivered_date')
                ?? Arr::get($item, 'deliveredDate')
                ?? Arr::get($item, 'delivery_date')
        );

        $status = $this->resolveLogisticsStatus($item, $purchaseDate, $loadingDate, $eta, $deliveredDate);

        $destination = $this->stringFrom($item, [
            'destination_port',
            'destination',
            'pod',
            'shippingDestination',
            'shipping_destination',
        ]) ?? $this->locationName(Arr::get($item, 'destination_location'));

        $loadingPoint = $this->stringFrom($item, [
            'loading_port',
            'loading_point',
            'loadingPoint',
            'pol',
            'pointOfLoading',
        ]) ?? $this->locationName(Arr::get($item, 'loading_location'));

        $auctionLocation = $this->stringFrom($item, ['auction_location', 'auctionLocation'])
            ?? $this->locationName(Arr::get($item, 'pickup_location'));

        $keys = $this->normalizeKeys(Arr::get($item, 'has_keys') ?? Arr::get($item, 'keys'));

        $imagePayload = $this->buildImagePayload($item, $media);
        $thumbnailUrl = $imagePayload['thumbnail_url'] ?? null;
        // VehicleImageStages drops thumbnail_url matches from galleries — keep stages complete.
        unset($imagePayload['thumbnail_url']);
        $imagesByStage = VehicleImageStages::resolve($imagePayload);
        $images = [];

        foreach (VehicleImageStages::STAGES as $stage) {
            foreach ($imagesByStage[$stage] as $url) {
                if (! in_array($url, $images, true)) {
                    $images[] = $url;
                }
            }
        }

        if (is_string($thumbnailUrl) && $thumbnailUrl !== '' && ! in_array($thumbnailUrl, $images, true)) {
            array_unshift($images, $thumbnailUrl);
        }

        $rawData = [
            ...$item,
            'source' => VehicleSource::AutoShipper->value,
            'vin' => $vin,
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'price' => $price,
            'color' => $this->stringFrom($item, ['color']),
            'fuel_type' => $this->stringFrom($item, ['fuel_type', 'fuelType', 'fuel']),
            'vehicle_type' => $this->stringFrom($item, ['body_type', 'bodyType', 'vehicle_type', 'type']),
            'eta' => $eta,
            'status' => $status,
            'lot' => $this->stringFrom($item, ['lot_number', 'lotNumber', 'lot']),
            'auction' => $this->stringFrom($item, ['auction_name', 'auctionName', 'auction']),
            'auction_location' => $auctionLocation,
            'city' => $auctionLocation,
            'buyer' => $this->stringFrom($item, ['buyer', 'buyerNumber', 'buyer_number', 'customer_id']),
            'destination' => $destination,
            'loading_point' => $loadingPoint,
            'booking_number' => $this->stringFrom($item, ['booking_id', 'booking_number', 'bookingNumber', 'booking']),
            'container_number' => $this->stringFrom($item, ['container_id', 'container_number', 'containerNumber', 'container']),
            'sea_line' => $this->stringFrom($item, ['sea_line', 'seaLine', 'shipping_line']),
            'purchase_date' => $purchaseDate,
            'loading_date' => $loadingDate,
            'date_pick' => $loadingDate,
            'arrived_terminal_date' => $loadingDate,
            'delivered_date' => $deliveredDate,
            'keys' => $keys,
            'notes' => $this->stringFrom($item, ['notes']),
            'thumbnail_url' => $thumbnailUrl,
            'media' => $media,
            'vehicle_charges' => $charges,
            'images' => $images,
            'images_by_stage' => $imagesByStage,
            'synced_at' => now()->toIso8601String(),
        ];

        $rawData = array_filter(
            $rawData,
            static fn ($value) => $value !== null && $value !== '',
        );

        return [
            'vin' => $vin,
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'price' => $price,
            'eta' => $eta,
            'images' => $images,
            'raw_data' => $rawData,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $media
     * @return array<string, mixed>
     */
    protected function buildImagePayload(array $item, array $media): array
    {
        $images = [];
        $byStage = [
            'terminal' => [],
            'pickup' => [],
            'destination' => [],
        ];

        $thumbnail = $this->stringFrom($item, ['thumbnail_url', 'thumbnailUrl', 'thumb', 'photo', 'image']);

        $thumbnailMediaId = $this->stringFrom($item, ['thumbnail_media_id', 'thumbnail_image_id', 'thumbnailMediaId']);
        if ($thumbnail === null && $thumbnailMediaId !== null) {
            foreach ($media as $row) {
                $mediaId = $this->stringFrom($row, ['_id', 'id']);
                if ($mediaId === $thumbnailMediaId) {
                    $thumbnail = $this->extractMediaUrl($row)
                        ?? $this->stringFrom($row, ['thumbnail_url', 'thumbnailUrl']);
                    break;
                }
            }
        }

        foreach ($media as $row) {
            $url = $this->extractMediaUrl($row);

            if ($url === null) {
                continue;
            }

            $stage = $this->resolveMediaStage($row, $url);
            $byStage[$stage][] = $url;
            $images[] = $url;
        }

        // Also accept images already on the vehicle payload.
        foreach (['images', 'image_urls', 'photos', 'gallery'] as $key) {
            $list = Arr::get($item, $key);
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $entry) {
                if (is_string($entry) && $this->isHttpUrl($entry)) {
                    $images[] = $entry;
                } elseif (is_array($entry)) {
                    $url = $this->extractMediaUrl($entry);
                    if ($url !== null) {
                        $images[] = $url;
                    }
                }
            }
        }

        if ($thumbnail !== null && ! in_array($thumbnail, $images, true)) {
            array_unshift($images, $thumbnail);
            if ($byStage['terminal'] === []) {
                $byStage['terminal'][] = $thumbnail;
            }
        }

        return [
            'thumbnail_url' => $thumbnail,
            'images' => array_values(array_unique($images)),
            'terminal' => ['urls' => array_values(array_unique($byStage['terminal']))],
            'pickup' => ['urls' => array_values(array_unique($byStage['pickup']))],
            'destination' => ['urls' => array_values(array_unique($byStage['destination']))],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractMediaUrl(array $row): ?string
    {
        foreach ([
            'url',
            'src',
            'path',
            'fileUrl',
            'file_url',
            'imageUrl',
            'image_url',
            'fullUrl',
            'full_url',
            'cdnUrl',
            'cdn_url',
            'thumbnail_url',
            'thumbnailUrl',
        ] as $key) {
            $value = Arr::get($row, $key);
            if (is_string($value) && $this->isHttpUrl($value)) {
                return $value;
            }
        }

        // Nested image object
        foreach (['image', 'file', 'media'] as $nestedKey) {
            $nested = Arr::get($row, $nestedKey);
            if (is_string($nested) && $this->isHttpUrl($nested)) {
                return $nested;
            }
            if (is_array($nested)) {
                foreach (['url', 'src', 'path', 'full_size', 'fullSize'] as $key) {
                    $value = Arr::get($nested, $key);
                    if (is_string($value) && $this->isHttpUrl($value)) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveMediaStage(array $row, string $url): string
    {
        $hint = $this->stringFrom($row, [
            'stage',
            'type',
            'location',
            'phase',
            'category',
            'mediaType',
            'media_type',
            'document_type',
            'tag',
        ]);

        if ($hint !== null) {
            $normalized = Str::lower($hint);
            if (str_contains($normalized, 'pickup') || str_contains($normalized, 'pick_up')) {
                return 'pickup';
            }
            if (
                str_contains($normalized, 'destination')
                || str_contains($normalized, 'delivery')
                || str_contains($normalized, 'dropoff')
                || str_contains($normalized, 'drop_off')
            ) {
                return 'destination';
            }
            if (
                str_contains($normalized, 'terminal')
                || str_contains($normalized, 'yard')
                || str_contains($normalized, 'auction')
                || str_contains($normalized, 'loading')
            ) {
                return 'terminal';
            }
        }

        $lower = Str::lower($url);
        if (str_contains($lower, 'pickup')) {
            return 'pickup';
        }
        if (str_contains($lower, 'destination') || str_contains($lower, 'delivery')) {
            return 'destination';
        }

        return 'terminal';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveLogisticsStatus(
        array $item,
        ?string $purchaseDate,
        ?string $loadingDate,
        ?string $eta,
        ?string $deliveredDate,
    ): ?string {
        $explicit = $this->stringFrom($item, [
            'status',
            'tracking_status',
            'trackingStatus',
            'tracking',
            'vehicleStatus',
            'currentStatus',
        ]);

        if ($explicit !== null) {
            return $explicit;
        }

        $snapshot = Arr::get($item, 'tracking_snapshot');
        if (is_array($snapshot)) {
            $fromSnapshot = $this->stringFrom($snapshot, ['status', 'state', 'label', 'name']);
            if ($fromSnapshot !== null) {
                return $fromSnapshot;
            }
        }

        if ($deliveredDate !== null) {
            return 'Delivered';
        }

        if ($eta !== null && $loadingDate !== null) {
            return 'On The Way';
        }

        if ($loadingDate !== null) {
            return 'Picked Up';
        }

        if ($purchaseDate !== null) {
            return 'Purchased';
        }

        return null;
    }

    protected function locationName(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        return $this->stringFrom($value, ['name', 'city', 'port', 'label', 'title']);
    }

    protected function normalizeKeys(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            $trimmed = trim((string) $value);

            return $trimmed !== '' ? $trimmed : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveExternalId(array $item): string
    {
        foreach (['_id', 'id', 'vehicleId', 'vehicle_id', 'autoshipper_id', 'externalId', 'external_id'] as $key) {
            $value = Arr::get($item, $key);
            if ($value !== null && $value !== '' && (is_string($value) || is_int($value) || is_float($value))) {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveRelatedVehicleId(array $row): string
    {
        foreach (['vehicleId', 'vehicle_id', 'vehicle', 'autoId', 'auto_id', 'carId', 'car_id'] as $key) {
            $value = Arr::get($row, $key);
            if (is_array($value)) {
                $nested = $this->resolveExternalId($value);
                if ($nested !== '') {
                    return $nested;
                }

                continue;
            }
            if ($value !== null && $value !== '' && (is_string($value) || is_int($value) || is_float($value))) {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     */
    protected function stringFrom(array $item, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($item, $key);
            if ($value === null || $value === '') {
                continue;
            }
            if (is_string($value) || is_int($value) || is_float($value)) {
                $trimmed = trim((string) $value);

                return $trimmed !== '' ? $trimmed : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     */
    protected function intFrom(array $item, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = Arr::get($item, $key);
            if ($value === null || $value === '') {
                continue;
            }
            if (is_numeric($value)) {
                $int = (int) $value;

                return $int > 0 ? $int : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $keys
     */
    protected function floatFrom(array $item, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = Arr::get($item, $key);
            if ($value === null || $value === '') {
                continue;
            }
            if (is_numeric($value)) {
                return round((float) $value, 2);
            }
            if (is_string($value)) {
                $clean = preg_replace('/[^\d.]/', '', $value);

                if ($clean !== null && $clean !== '' && is_numeric($clean)) {
                    return round((float) $clean, 2);
                }
            }
        }

        return null;
    }

    protected function isHttpUrl(string $value): bool
    {
        $trimmed = trim($value);

        return $trimmed !== ''
            && (str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'http://'))
            && ! str_contains($trimmed, 'no_photo');
    }

    /**
     * @param  array<int, array{id: int, vin: string|null}>  $restorableById
     */
    protected function trackRestorable(array &$restorableById, Vehicle $vehicle): void
    {
        $restorableById[$vehicle->id] = [
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
        ];
    }
}
