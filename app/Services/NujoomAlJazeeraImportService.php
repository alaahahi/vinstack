<?php

namespace App\Services;

use App\Enums\NujoomImportApplyMode;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Support\VehicleEta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class NujoomAlJazeeraImportService
{
    public const PREVIEW_TTL_SECONDS = 1800;

    /**
     * @var array<string, string>
     */
    public const HEADER_MAP = [
        'No' => 'row_no',
        'Auction Photo' => 'auction_photo',
        'Lot & Vin' => 'lot_and_vin',
        'Auction' => 'auction',
        'Region' => 'region',
        'Destination' => 'destination',
        'Purchase Date' => 'purchase_date',
        'Auction Price' => 'auction_price',
        'Auction Invoice' => 'auction_invoice',
        'Payment Date' => 'payment_date',
        'Date Pick' => 'date_pick',
        'Pick up note' => 'pick_up_note',
        'Arrived Date' => 'arrived_date',
        'Title' => 'title',
        'Title Note' => 'title_note',
        'Title Date' => 'title_date',
        'Key' => 'key',
        'Point of loading' => 'loading_point',
        'Loaded Date' => 'loaded_date',
        'Dock Receipt' => 'dock_receipt',
        'Booking' => 'booking_number',
        'Container' => 'container_number',
        'ETD' => 'etd',
        'Shipping Date' => 'shipping_date',
        'ETA' => 'eta',
        'Store Arrival' => 'store_arrival',
        'Paid' => 'paid',
        'Sold' => 'sold',
        'Tracking' => 'tracking',
    ];

    public function __construct(
        protected ContainerService $containers,
    ) {}

    /**
     * @return array{
     *     preview_token: string,
     *     counts: array{to_add: int, to_update: int, containers_new: int, errors: int},
     *     to_add: list<array<string, mixed>>,
     *     to_update: list<array<string, mixed>>,
     *     containers_new: list<array<string, mixed>>,
     *     errors: list<array<string, mixed>>,
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->parseSpreadsheet($file);
        $preview = $this->buildPreview($rows);
        $token = (string) Str::uuid();

        Cache::put($this->cacheKey($token), $preview, self::PREVIEW_TTL_SECONDS);

        return [
            'preview_token' => $token,
            'counts' => [
                'to_add' => count($preview['to_add']),
                'to_update' => count($preview['to_update']),
                'containers_new' => count($preview['containers_new']),
                'errors' => count($preview['errors']),
            ],
            'to_add' => $preview['to_add'],
            'to_update' => $preview['to_update'],
            'containers_new' => $preview['containers_new'],
            'errors' => $preview['errors'],
        ];
    }

    /**
     * @return array{created: int, updated: int, containers_new: int, mode: string}
     */
    public function apply(string $previewToken, NujoomImportApplyMode $mode = NujoomImportApplyMode::All): array
    {
        $preview = Cache::get($this->cacheKey($previewToken));

        if (! is_array($preview)) {
            throw new RuntimeException('انتهت صلاحية المعاينة — يرجى رفع الملف مرة أخرى.');
        }

        $created = 0;
        $updated = 0;

        $full = $preview['_full'] ?? null;

        if (! is_array($full)) {
            throw new RuntimeException('انتهت صلاحية المعاينة — يرجى رفع الملف مرة أخرى.');
        }

        $shouldAdd = $mode !== NujoomImportApplyMode::UpdatesOnly;
        $shouldUpdate = $mode !== NujoomImportApplyMode::AddOnly;

        DB::transaction(function () use ($full, $shouldAdd, $shouldUpdate, &$created, &$updated) {
            if ($shouldAdd) {
                foreach ($full['to_add'] as $item) {
                    $this->createVehicle($item);
                    $created++;
                }
            }

            if ($shouldUpdate) {
                foreach ($full['to_update'] as $item) {
                    $vehicle = Vehicle::query()->find($item['vehicle_id']);

                    if ($vehicle === null) {
                        continue;
                    }

                    $this->updateVehicle($vehicle, $item);
                    $updated++;
                }
            }
        });

        Cache::forget($this->cacheKey($previewToken));

        return [
            'created' => $created,
            'updated' => $updated,
            'containers_new' => $this->containersNewCountForMode($preview, $mode),
            'mode' => $mode->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    protected function containersNewCountForMode(array $preview, NujoomImportApplyMode $mode): int
    {
        if ($mode === NujoomImportApplyMode::UpdatesOnly) {
            return 0;
        }

        if ($mode === NujoomImportApplyMode::AddOnly) {
            $newVehicleContainers = [];

            foreach ($preview['to_add'] ?? [] as $item) {
                $number = $this->normalizeContainerNumber($item['container_number'] ?? null);

                if ($number !== null) {
                    $newVehicleContainers[$number] = true;
                }
            }

            return count(array_filter(
                $preview['containers_new'] ?? [],
                function (array $container) use ($newVehicleContainers) {
                    $number = $this->normalizeContainerNumber($container['container_number'] ?? null);

                    return $number !== null && isset($newVehicleContainers[$number]);
                },
            ));
        }

        return count($preview['containers_new'] ?? []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);

        if ($matrix === []) {
            return [];
        }

        $headerRow = array_shift($matrix);
        $columnKeys = $this->mapHeaders($headerRow);
        $rows = [];

        foreach ($matrix as $index => $cells) {
            $excelRowNumber = $index + 2;
            $assoc = [];

            foreach ($columnKeys as $colIndex => $key) {
                if ($key === null) {
                    continue;
                }

                $assoc[$key] = $this->normalizeCellValue($cells[$colIndex] ?? null);
            }

            if ($this->rowIsEmpty($assoc)) {
                continue;
            }

            $assoc['_excel_row'] = $excelRowNumber;
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     to_add: list<array<string, mixed>>,
     *     to_update: list<array<string, mixed>>,
     *     containers_new: list<array<string, mixed>>,
     *     errors: list<array<string, mixed>>,
     *     payloads: list<array<string, mixed>>,
     * }
     */
    public function buildPreview(array $rows): array
    {
        $existingContainers = $this->existingContainerNumbers();
        $seenVins = [];
        $toAdd = [];
        $toUpdate = [];
        $errors = [];
        $payloads = [];
        $newContainers = [];

        /** @var array<string, array{container_number: ?string, booking_number: ?string, loading_point: ?string, destination: ?string, vehicle_count: int}> $pendingContainers */
        $pendingContainers = [];

        foreach ($rows as $row) {
            $excelRow = (int) ($row['_excel_row'] ?? 0);

            try {
                $mapped = $this->mapRow($row);
            } catch (RuntimeException $e) {
                $errors[] = [
                    'row' => $excelRow,
                    'message' => $e->getMessage(),
                ];

                continue;
            }

            $vin = $mapped['vin'];

            if (isset($seenVins[$vin])) {
                $errors[] = [
                    'row' => $excelRow,
                    'message' => "رقم الشاصي مكرر في الملف (الصف {$seenVins[$vin]}).",
                ];

                continue;
            }

            $seenVins[$vin] = $excelRow;
            $payloads[] = $mapped;

            $containerNumber = $mapped['container_number'] ?? null;

            if ($containerNumber !== null && $containerNumber !== '') {
                $normalizedContainer = $this->normalizeContainerNumber($containerNumber);

                if ($normalizedContainer !== null && ! isset($existingContainers[$normalizedContainer])) {
                    if (! isset($pendingContainers[$normalizedContainer])) {
                        $pendingContainers[$normalizedContainer] = [
                            'container_number' => $containerNumber,
                            'booking_number' => $mapped['booking_number'] ?? null,
                            'loading_point' => $mapped['loading_point'] ?? null,
                            'destination' => $mapped['destination'] ?? null,
                            'vehicle_count' => 0,
                        ];
                    }

                    $pendingContainers[$normalizedContainer]['vehicle_count']++;
                }
            }

            $vehicle = Vehicle::query()->where('vin', $vin)->first();

            $summary = $this->summarizeMappedRow($mapped);

            if ($vehicle !== null) {
                $toUpdate[] = [
                    ...$summary,
                    'vehicle_id' => $vehicle->id,
                    'existing_source' => $vehicle->source?->value,
                    'payload' => $mapped,
                ];
            } else {
                $toAdd[] = [
                    ...$summary,
                    'payload' => $mapped,
                ];
            }
        }

        foreach ($pendingContainers as $container) {
            $newContainers[] = $container;
        }

        return [
            'to_add' => $this->stripPayloads($toAdd),
            'to_update' => $this->stripPayloads($toUpdate),
            'containers_new' => $newContainers,
            'errors' => $errors,
            'payloads' => $payloads,
            '_full' => [
                'to_add' => $toAdd,
                'to_update' => $toUpdate,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function mapRow(array $row): array
    {
        $lotAndVin = (string) ($row['lot_and_vin'] ?? '');
        $parsed = $this->parseLotAndVin($lotAndVin);
        $vehicleLine = (string) ($row['auction_photo'] ?? '');
        $ymm = $this->parseYearMakeModel($vehicleLine);
        $auctionBlock = trim((string) ($row['auction'] ?? ''));
        $buyer = $this->parseBuyer($auctionBlock);

        $vin = $parsed['vin'];

        if ($vin === null || ! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            throw new RuntimeException('رقم الشاصي غير صالح أو مفقود.');
        }

        $eta = VehicleEta::normalize($row['eta'] ?? null);

        $rawData = [
            'source' => VehicleSource::NujoomAlJazeera->value,
            'vin' => $vin,
            'lot' => $parsed['lot'],
            'make' => $ymm['make'],
            'model' => $ymm['model'],
            'year' => $ymm['year'],
            'auction' => $this->normalizeAuction($auctionBlock),
            'buyer' => $buyer,
            'region' => $this->nullableString($row['region'] ?? null),
            'destination' => $this->nullableString($row['destination'] ?? null),
            'purchase_date' => VehicleEta::normalize($row['purchase_date'] ?? null),
            'value' => $this->nullableString($row['auction_price'] ?? null),
            'auction_invoice' => $this->nullableString($row['auction_invoice'] ?? null),
            'payment_date' => $this->nullableString($row['payment_date'] ?? null),
            'date_pick' => $this->nullableString($row['date_pick'] ?? null),
            'pick_up_note' => $this->nullableString($row['pick_up_note'] ?? null),
            'arrived_terminal_date' => $this->nullableString($row['arrived_date'] ?? null),
            'title_status' => $this->nullableString($row['title'] ?? null),
            'title_note' => $this->nullableString($row['title_note'] ?? null),
            'title_date' => $this->nullableString($row['title_date'] ?? null),
            'keys' => $this->nullableString($row['key'] ?? null),
            'loading_point' => $this->nullableString($row['loading_point'] ?? null),
            'loading_date' => $this->nullableString($row['loaded_date'] ?? null),
            'dock_receipt' => $this->nullableString($row['dock_receipt'] ?? null),
            'booking_number' => $this->nullableString($row['booking_number'] ?? null),
            'container_number' => $this->nullableString($row['container_number'] ?? null),
            'etd' => $this->nullableString($row['etd'] ?? null),
            'shipping_date' => $this->nullableString($row['shipping_date'] ?? null),
            'eta' => $eta,
            'store_arrival' => $this->nullableString($row['store_arrival'] ?? null),
            'paid' => $this->nullableString($row['paid'] ?? null),
            'sold' => $this->nullableString($row['sold'] ?? null),
            'status' => $this->nullableString($row['tracking'] ?? null),
            'nujoom_excel_row' => $row,
            'imported_at' => now()->toIso8601String(),
        ];

        $rawData = array_filter(
            $rawData,
            fn ($value) => $value !== null && $value !== '',
        );

        return [
            'vin' => $vin,
            'make' => $ymm['make'],
            'model' => $ymm['model'],
            'year' => $ymm['year'],
            'price' => $this->parsePrice($row['auction_price'] ?? null),
            'eta' => $eta,
            'container_number' => $rawData['container_number'] ?? null,
            'booking_number' => $rawData['booking_number'] ?? null,
            'loading_point' => $rawData['loading_point'] ?? null,
            'destination' => $rawData['destination'] ?? null,
            'lot' => $parsed['lot'],
            'auction' => $rawData['auction'] ?? null,
            'raw_data' => $rawData,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function createVehicle(array $item): Vehicle
    {
        $payload = $item['payload'] ?? $item;
        $rawData = $payload['raw_data'] ?? [];
        $rawData['created_at'] = now()->toIso8601String();

        return Vehicle::query()->create([
            'source' => VehicleSource::NujoomAlJazeera,
            'vinstack_id' => null,
            'vin' => $payload['vin'],
            'make' => $payload['make'],
            'model' => $payload['model'],
            'year' => $payload['year'],
            'price' => $payload['price'],
            'eta' => $payload['eta'] ?? null,
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => $rawData,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function updateVehicle(Vehicle $vehicle, array $item): void
    {
        $payload = $item['payload'] ?? $item;
        $incomingRaw = is_array($payload['raw_data'] ?? null) ? $payload['raw_data'] : [];
        $existingRaw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

        $rawData = [
            ...$existingRaw,
            ...$incomingRaw,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($vehicle->source !== VehicleSource::NujoomAlJazeera) {
            $rawData['source'] = $vehicle->source?->value ?? VehicleSource::Vinstack->value;
        }

        $vehicle->update([
            'vin' => $payload['vin'],
            'make' => $payload['make'] ?: $vehicle->make,
            'model' => $payload['model'] ?: $vehicle->model,
            'year' => $payload['year'] ?: $vehicle->year,
            'price' => $payload['price'] ?? $vehicle->price,
            'eta' => $payload['eta'] ?? $vehicle->eta,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * @param  list<string|null>  $headerRow
     * @return array<int, string|null>
     */
    protected function mapHeaders(array $headerRow): array
    {
        $keys = [];

        foreach ($headerRow as $index => $label) {
            $label = trim((string) $label);
            $keys[$index] = self::HEADER_MAP[$label] ?? null;
        }

        return $keys;
    }

    protected function normalizeCellValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_float($value) && $value > 30000 && $value < 60000) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable) {
                // Not an Excel serial date.
            }
        }

        $string = trim((string) $value);

        if ($string === '' || strtoupper($string) === 'N A') {
            return null;
        }

        return $string;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        $values = Arr::except($row, ['_excel_row', 'row_no']);

        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{lot: ?string, vin: ?string}
     */
    public function parseLotAndVin(string $value): array
    {
        $lot = null;
        $vin = null;

        if (preg_match('/Lot#\s*(\d+)/i', $value, $lotMatch)) {
            $lot = trim($lotMatch[1]);
        }

        if (preg_match('/Vin#\s*([A-HJ-NPR-Z0-9]{17})/i', $value, $vinMatch)) {
            $vin = strtoupper(trim($vinMatch[1]));
        }

        return [
            'lot' => $lot,
            'vin' => $vin,
        ];
    }

    /**
     * @return array{make: ?string, model: ?string, year: ?int}
     */
    public function parseYearMakeModel(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return ['make' => null, 'model' => null, 'year' => null];
        }

        $year = null;

        if (preg_match('/\b(19|20)\d{2}\b/', $value, $yearMatch, PREG_OFFSET_CAPTURE)) {
            $year = (int) $yearMatch[0][0];
            $value = trim(substr($value, 0, $yearMatch[0][1]));
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        $make = isset($parts[0]) ? $this->titleCase($parts[0]) : null;
        $model = count($parts) > 1 ? $this->titleCase(implode(' ', array_slice($parts, 1))) : null;

        return [
            'make' => $make,
            'model' => $model,
            'year' => $year,
        ];
    }

    protected function parseBuyer(string $auctionBlock): ?string
    {
        if (preg_match('/Buyer Number:\s*(.+)$/im', $auctionBlock, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    protected function normalizeAuction(string $auctionBlock): ?string
    {
        $lines = preg_split('/\R/', $auctionBlock) ?: [];
        $first = trim($lines[0] ?? '');

        return $first !== '' ? $first : null;
    }

    protected function parsePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $clean = preg_replace('/[^\d.]/', '', (string) $value);

        return $clean !== '' && is_numeric($clean) ? round((float) $clean, 2) : null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }

    protected function titleCase(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return Str::title(strtolower($value));
    }

    protected function normalizeContainerNumber(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<string, true>
     */
    protected function existingContainerNumbers(): array
    {
        $numbers = [];

        foreach ($this->containers->listForAdmin() as $container) {
            $number = $this->normalizeContainerNumber($container['container_number'] ?? null);

            if ($number !== null) {
                $numbers[$number] = true;
            }
        }

        foreach (Vehicle::query()->get(['raw_data']) as $vehicle) {
            $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
            $number = $this->normalizeContainerNumber($raw['container_number'] ?? null);

            if ($number !== null) {
                $numbers[$number] = true;
            }
        }

        return $numbers;
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @return array<string, mixed>
     */
    protected function summarizeMappedRow(array $mapped): array
    {
        return [
            'vin' => $mapped['vin'],
            'make' => $mapped['make'],
            'model' => $mapped['model'],
            'year' => $mapped['year'],
            'eta' => $mapped['eta'] ?? null,
            'container_number' => $mapped['container_number'] ?? null,
            'booking_number' => $mapped['booking_number'] ?? null,
            'destination' => $mapped['destination'] ?? null,
            'lot' => $mapped['lot'] ?? null,
            'auction' => $mapped['auction'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function stripPayloads(array $items): array
    {
        return array_map(
            fn (array $item) => Arr::except($item, ['payload']),
            $items,
        );
    }

    protected function cacheKey(string $token): string
    {
        return 'nujoom_import_preview:'.$token;
    }
}
