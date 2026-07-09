<?php

namespace App\Services;

use App\Models\ContainerImage;
use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContainerService
{
    /** @var list<array<string, mixed>>|null */
    protected ?array $normalizedContainersCache = null;

    public function __construct(
        protected VinstackService $vinstack,
        protected VehicleUploadedImageService $gallery,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->listForAdminFiltered();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdminFiltered(?int $dealerId = null, ?string $chassis = null): array
    {
        if ($dealerId !== null) {
            $dealer = Dealer::query()->findOrFail($dealerId);
            $items = $this->listForDealer($dealer);
        } else {
            $fromApi = $this->fetchNormalized();
            $fromVehicles = $this->deriveFromAllVehicles();
            $items = $this->mergeDealerContainerLists($fromApi, $fromVehicles);
        }

        if ($chassis !== null && trim($chassis) !== '') {
            $needle = strtoupper(trim($chassis));
            $items = array_values(array_filter(
                $items,
                fn (array $container) => $this->containerMatchesChassis($container, $needle),
            ));
        }

        return $this->attachListImageSummaries($items);
    }

    /**
     * @param  array<string, mixed>  $container
     */
    protected function containerMatchesChassis(array $container, string $needle): bool
    {
        foreach ($container['vehicles'] ?? [] as $vehicle) {
            if (! is_array($vehicle)) {
                continue;
            }

            $vin = strtoupper(trim((string) ($vehicle['vin'] ?? '')));

            if ($vin !== '' && str_contains($vin, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForDealer(Dealer $dealer): array
    {
        $keys = $this->dealerMatchKeys($dealer);
        $items = $this->fetchNormalized();

        $fromApi = array_values(array_filter(
            $items,
            fn (array $container) => $this->matchesDealer($container, $keys),
        ));

        $fromVehicles = $this->deriveFromDealerVehicles($dealer);

        $merged = $this->mergeDealerContainerLists($fromApi, $fromVehicles);

        $this->logDealerContainerEdgeCases($dealer, $keys, $items, $fromApi, $fromVehicles, $merged);

        return $this->attachListImageSummaries($merged);
    }

    public function trackingAvailable(): bool
    {
        return true;
    }

    /**
     * @return array{container: array<string, mixed>, vehicles: list<array<string, mixed>>}|null
     */
    public function vehiclesForContainer(string $ref, ?Dealer $dealer = null): ?array
    {
        $containerNumber = $this->normalizeContainerNumber($ref);
        $bookingNumber = $this->normalizeBookingNumber($ref);

        if (($containerNumber === null || $containerNumber === '')
            && ($bookingNumber === null || $bookingNumber === '')) {
            return null;
        }

        $container = $this->findContainerByRef($containerNumber, $bookingNumber, $dealer);

        if ($container === null) {
            return null;
        }

        return [
            'container' => $this->containerDetailHeader($container),
            'vehicles' => $this->buildContainerVehicleDetails($container, $dealer),
        ];
    }

    /**
     * Resolve normalized refs that may have been used when persisting container gallery images.
     *
     * @param  array<string, mixed>  $containerHeader
     * @return list<string>
     */
    public function imageLookupKeysForContainer(string $urlRef, array $containerHeader): array
    {
        $keys = [];

        foreach ([$urlRef, $containerHeader['container_number'] ?? null, $containerHeader['booking_number'] ?? null] as $candidate) {
            $container = $this->normalizeContainerNumber($candidate);

            if ($container !== null && $container !== '') {
                $keys[] = $container;
            }

            $booking = $this->normalizeBookingNumber($candidate);

            if ($booking !== null && $booking !== '') {
                $keys[] = $booking;
            }
        }

        $booking = $this->normalizeBookingNumber($containerHeader['booking_number'] ?? $urlRef);

        if ($booking !== null && $booking !== '') {
            foreach ($this->fetchNormalized() as $row) {
                $rowBooking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

                if ($rowBooking !== $booking) {
                    continue;
                }

                $containerNumber = $this->normalizeContainerNumber($row['container_number'] ?? null);

                if ($containerNumber !== null && $containerNumber !== '') {
                    $keys[] = $containerNumber;
                }
            }
        }

        $containerNumber = $this->normalizeContainerNumber($containerHeader['container_number'] ?? $urlRef);

        if ($containerNumber !== null && $containerNumber !== '') {
            foreach ($this->fetchNormalized() as $row) {
                $rowContainer = $this->normalizeContainerNumber($row['container_number'] ?? null);

                if ($rowContainer !== $containerNumber) {
                    continue;
                }

                $rowBooking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

                if ($rowBooking !== null && $rowBooking !== '') {
                    $keys[] = $rowBooking;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findContainerByRef(?string $containerNumber, ?string $bookingNumber, ?Dealer $dealer): ?array
    {
        $items = $dealer !== null
            ? $this->listForDealer($dealer)
            : $this->listForAdminFiltered();

        foreach ($items as $row) {
            $rowContainer = $this->normalizeContainerNumber($row['container_number'] ?? null);
            $rowBooking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

            if ($containerNumber !== null && $containerNumber !== '' && $rowContainer === $containerNumber) {
                return $row;
            }

            if ($bookingNumber !== null && $bookingNumber !== '' && $rowBooking === $bookingNumber) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>
     */
    protected function containerDetailHeader(array $container): array
    {
        return [
            'id' => $container['id'] ?? null,
            'container_number' => $container['container_number'] ?? null,
            'booking_number' => $container['booking_number'] ?? null,
            'seal_number' => $container['seal_number'] ?? null,
            'customer_name' => $container['customer_name'] ?? null,
            'loading_point' => $container['loading_point'] ?? null,
            'destination' => $container['destination'] ?? null,
            'shipping_line' => $container['shipping_line'] ?? null,
            'loading_date' => $container['loading_date'] ?? null,
            'eta' => $container['eta'] ?? null,
            'status' => $container['status'] ?? null,
            'released' => (bool) ($container['released'] ?? false),
            'invoice_ref' => $container['invoice_ref'] ?? null,
            'bol_url' => $container['bol_url'] ?? null,
            'vehicle_count' => count($container['vehicles'] ?? []),
            'source' => $container['source'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $container
     * @return list<array<string, mixed>>
     */
    protected function buildContainerVehicleDetails(array $container, ?Dealer $dealer): array
    {
        $containerNumber = $this->normalizeContainerNumber($container['container_number'] ?? null);
        $bookingNumber = $this->normalizeBookingNumber($container['booking_number'] ?? null);

        $apiAutos = $this->fetchContainerAutos($containerNumber);
        $dbVehicles = $this->queryVehiclesInContainer($containerNumber, $bookingNumber, $dealer);

        /** @var array<string, Vehicle> $dbByVin */
        $dbByVin = [];

        foreach ($dbVehicles as $vehicle) {
            $vin = $this->normalizeVin((string) $vehicle->vin);

            if ($vin !== '') {
                $dbByVin[$vin] = $vehicle;
            }
        }

        $merged = [];
        $seenVins = [];

        foreach ($apiAutos as $auto) {
            if (! is_array($auto)) {
                continue;
            }

            $vin = $this->normalizeVin($this->string($auto, 'vin') ?? '');

            if ($vin !== '') {
                $seenVins[$vin] = true;
            }

            $merged[] = $this->formatContainerVehicleRow(
                $auto,
                $vin !== '' ? ($dbByVin[$vin] ?? null) : null,
            );
        }

        foreach ($dbVehicles as $vehicle) {
            $vin = $this->normalizeVin((string) $vehicle->vin);

            if ($vin !== '' && isset($seenVins[$vin])) {
                continue;
            }

            if ($vin !== '') {
                $seenVins[$vin] = true;
            }

            $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
            $merged[] = $this->formatContainerVehicleRow($raw, $vehicle);
        }

        foreach ($container['vehicles'] ?? [] as $summary) {
            if (! is_array($summary)) {
                continue;
            }

            $vin = $this->normalizeVin((string) ($summary['vin'] ?? ''));

            if ($vin !== '' && isset($seenVins[$vin])) {
                continue;
            }

            if ($vin !== '') {
                $seenVins[$vin] = true;
            }

            $merged[] = $this->formatContainerVehicleRow(
                $summary,
                $vin !== '' ? ($dbByVin[$vin] ?? null) : null,
            );
        }

        return $merged;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchContainerAutos(?string $containerNumber): array
    {
        if ($containerNumber === null || $containerNumber === '') {
            return [];
        }

        try {
            $detail = $this->vinstack->container($containerNumber);
        } catch (RuntimeException) {
            return [];
        }

        $autos = Arr::get($detail, 'autos', Arr::get($detail, 'vehicles', []));

        if (! is_array($autos)) {
            return [];
        }

        return array_values(array_filter($autos, fn ($item) => is_array($item)));
    }

    /**
     * @return Collection<int, Dealer>
     */
    public function dealersForContainer(string $container): Collection
    {
        $number = $this->normalizeContainerNumber($container);

        if ($number === null || $number === '') {
            return collect();
        }

        $vehicles = $this->queryVehiclesInContainer($number, null, null);

        return collect($vehicles)
            ->map(function (Vehicle $vehicle) {
                $vehicle->loadMissing('activeAssignment.dealer');

                return $vehicle->activeAssignment?->dealer;
            })
            ->filter(fn ($dealer) => $dealer instanceof Dealer)
            ->unique('id')
            ->values();
    }

    /**
     * @return list<Vehicle>
     */
    protected function queryVehiclesInContainer(?string $containerNumber, ?string $bookingNumber, ?Dealer $dealer): array
    {
        $query = Vehicle::query();

        if ($dealer !== null) {
            $query->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            });
        }

        return $query->get()
            ->filter(function (Vehicle $vehicle) use ($containerNumber, $bookingNumber) {
                $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
                $shipping = $this->extractShippingFromRaw($raw);
                $cn = $this->normalizeContainerNumber($shipping['container_number'] ?? null);
                $bk = $this->normalizeBookingNumber($shipping['booking_number'] ?? null);

                if ($containerNumber !== null && $containerNumber !== '' && $cn === $containerNumber) {
                    return true;
                }

                if ($bookingNumber !== null && $bookingNumber !== '' && $bk === $bookingNumber) {
                    return true;
                }

                return false;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    protected function formatContainerVehicleRow(array $source, ?Vehicle $dbVehicle = null): array
    {
        if ($dbVehicle !== null) {
            $enriched = $this->gallery->enrichListVehicle($dbVehicle);

            $vin = $this->string($source, 'vin') ?? $enriched['vin'] ?? null;
            $lot = $this->string($source, 'lot')
                ?? (is_array($enriched['raw_data'] ?? null) ? $this->string($enriched['raw_data'], 'lot') : null);
            $auction = $this->string($source, 'auction')
                ?? (is_array($enriched['raw_data'] ?? null) ? $this->string($enriched['raw_data'], 'auction') : null);
            $destination = $this->string($source, 'destination')
                ?? (is_array($enriched['raw_data'] ?? null) ? $this->string($enriched['raw_data'], 'destination') : null);
            $purchaseDate = $this->string($source, 'purchase_date')
                ?? (is_array($enriched['raw_data'] ?? null) ? $this->string($enriched['raw_data'], 'purchase_date') : null);
            $thumbnail = $this->string($source, 'thumbnail_url') ?? $enriched['thumbnail_url'] ?? null;

            $enriched['vin'] = $vin;
            $enriched['lot'] = $lot;
            $enriched['auction'] = $auction;
            $enriched['destination'] = $destination;
            $enriched['purchase_date'] = $purchaseDate;

            if (is_string($thumbnail) && $thumbnail !== '') {
                $enriched['thumbnail_url'] = $thumbnail;
            }

            return $enriched;
        }

        $year = Arr::get($source, 'year');
        $make = $this->string($source, 'make');
        $model = $this->string($source, 'model');
        $vin = $this->string($source, 'vin');

        return [
            'id' => null,
            'vin' => $vin,
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'lot' => $this->string($source, 'lot'),
            'auction' => $this->string($source, 'auction'),
            'destination' => $this->string($source, 'destination'),
            'purchase_date' => $this->string($source, 'purchase_date'),
            'thumbnail_url' => $this->string($source, 'thumbnail_url'),
            'images' => [],
            'images_by_stage' => [
                'terminal' => [],
                'pickup' => [],
                'destination' => [],
            ],
            'uploaded_images' => [],
            'raw_data' => $source,
            'source' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchNormalized(): array
    {
        if ($this->normalizedContainersCache !== null) {
            return $this->normalizedContainersCache;
        }

        try {
            $raw = $this->vinstack->containers();
        } catch (RuntimeException) {
            $this->normalizedContainersCache = [];

            return $this->normalizedContainersCache;
        }

        $this->normalizedContainersCache = array_values(array_map(
            fn (array $item) => $this->normalize($item),
            array_filter($raw, fn ($item) => is_array($item)),
        ));

        return $this->normalizedContainersCache;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function normalize(array $item): array
    {
        $autos = Arr::get($item, 'autos', Arr::get($item, 'vehicles', []));

        if (! is_array($autos)) {
            $autos = [];
        }

        return [
            'id' => Arr::get($item, 'id'),
            'container_number' => $this->string($item, 'container_number'),
            'booking_number' => $this->string($item, 'booking_number'),
            'seal_number' => $this->string($item, 'seal_number')
                ?: $this->string($item, 'seal'),
            'customer_name' => $this->string($item, 'customer_name')
                ?: $this->string($item, 'customer')
                ?: $this->string($item, 'buyer'),
            'loading_point' => $this->string($item, 'loading_point'),
            'destination' => $this->string($item, 'destination'),
            'shipping_line' => $this->string($item, 'shipping_line'),
            'size' => $this->string($item, 'size'),
            'loading_date' => $this->string($item, 'loading_date'),
            'eta' => $this->string($item, 'eta')
                ?: $this->string($item, 'estimated_arrival')
                ?: $this->string($item, 'eta_date'),
            'status' => $this->normalizeListStatus($item),
            'released' => (bool) (Arr::get($item, 'released') ?? Arr::get($item, 'is_released')),
            'bol_url' => $this->bolUrl($item),
            'invoice_ref' => $this->invoiceRef($item),
            'vehicles' => $this->normalizeAutos($autos),
            'tracking_available' => $this->rowTrackingAvailable($item),
            'source' => 'vinstack',
        ];
    }

    /**
     * @param  list<mixed>  $autos
     * @return list<array{vin: ?string, year: mixed, make: ?string, model: ?string, title: string}>
     */
    protected function normalizeAutos(array $autos): array
    {
        $vehicles = [];

        foreach ($autos as $auto) {
            if (! is_array($auto)) {
                continue;
            }

            $year = Arr::get($auto, 'year');
            $make = $this->string($auto, 'make');
            $model = $this->string($auto, 'model');
            $vin = $this->string($auto, 'vin');

            $title = trim(implode(' ', array_filter([
                $year !== null && $year !== '' ? (string) $year : null,
                $make,
                $model,
            ])));

            $vehicles[] = [
                'vin' => $vin,
                'year' => $year,
                'make' => $make,
                'model' => $model,
                'title' => $title !== '' ? $title : ($vin ?: '—'),
            ];
        }

        return $vehicles;
    }

    /**
     * @return array{vins: list<string>, container_numbers: list<string>, booking_numbers: list<string>}
     */
    protected function dealerMatchKeys(Dealer $dealer): array
    {
        $vehicles = Vehicle::query()
            ->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            })
            ->get(['id', 'vin', 'year', 'make', 'model', 'raw_data']);

        $vins = [];
        $containerNumbers = [];
        $bookingNumbers = [];

        foreach ($vehicles as $vehicle) {
            $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
            $shipping = $this->extractShippingFromRaw($raw);

            $vin = $this->normalizeVin((string) ($vehicle->vin ?: ($shipping['vin'] ?? '')));

            if ($vin !== '') {
                $vins[] = $vin;
            }

            $container = $this->normalizeContainerNumber($shipping['container_number'] ?? null);

            if ($container !== null && $container !== '') {
                $containerNumbers[] = $container;
            }

            $booking = $this->normalizeBookingNumber($shipping['booking_number'] ?? null);

            if ($booking !== null && $booking !== '') {
                $bookingNumbers[] = $booking;
            }
        }

        return [
            'vins' => array_values(array_unique($vins)),
            'container_numbers' => array_values(array_unique($containerNumbers)),
            'booking_numbers' => array_values(array_unique($bookingNumbers)),
        ];
    }

    /**
     * @param  array<string, mixed>  $container
     * @param  array{vins: list<string>, container_numbers: list<string>, booking_numbers: list<string>}  $keys
     */
    protected function matchesDealer(array $container, array $keys): bool
    {
        $containerNumber = $this->normalizeContainerNumber($container['container_number'] ?? null);
        $bookingNumber = $this->normalizeBookingNumber($container['booking_number'] ?? null);

        if ($containerNumber !== null && $containerNumber !== ''
            && in_array($containerNumber, $keys['container_numbers'], true)) {
            return true;
        }

        if ($bookingNumber !== null && $bookingNumber !== ''
            && in_array($bookingNumber, $keys['booking_numbers'], true)) {
            return true;
        }

        foreach ($container['vehicles'] ?? [] as $vehicle) {
            $vin = $this->normalizeVin((string) ($vehicle['vin'] ?? ''));

            if ($vin !== '' && in_array($vin, $keys['vins'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build container rows from vehicle raw_data (manual entry or sync) for the admin list.
     *
     * @return list<array<string, mixed>>
     */
    protected function deriveFromAllVehicles(): array
    {
        return $this->buildContainersFromVehicles(
            Vehicle::query()->get(),
        );
    }

    /**
     * Build container rows from synced vehicle raw_data when Vinstack list is empty or unmatched.
     *
     * @return list<array<string, mixed>>
     */
    protected function deriveFromDealerVehicles(Dealer $dealer): array
    {
        return $this->buildContainersFromVehicles(
            Vehicle::query()
                ->whereHas('assignments', function ($q) use ($dealer) {
                    $q->where('dealer_id', $dealer->id)->where('is_active', true);
                })
                ->get(),
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Vehicle>|\Illuminate\Database\Eloquent\Collection<int, Vehicle>  $vehicles
     * @return list<array<string, mixed>>
     */
    protected function buildContainersFromVehicles($vehicles): array
    {
        /** @var array<string, list<Vehicle>> $groups */
        $groups = [];

        foreach ($vehicles as $vehicle) {
            $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
            $shipping = $this->extractShippingFromRaw($raw);
            $groupKey = $this->containerListKey([
                'container_number' => $shipping['container_number'] ?? null,
                'booking_number' => $shipping['booking_number'] ?? null,
            ]);

            if ($groupKey === '') {
                continue;
            }

            $groups[$groupKey][] = $vehicle;
        }

        $containers = [];

        foreach ($groups as $group) {
            $firstRaw = is_array($group[0]->raw_data) ? $group[0]->raw_data : [];
            $shipping = $this->extractShippingFromRaw($firstRaw);
            $containerNumber = $shipping['container_number'] ?? null;
            $bookingNumber = $shipping['booking_number'] ?? null;

            $containers[] = [
                'id' => null,
                'container_number' => $containerNumber,
                'booking_number' => $bookingNumber,
                'seal_number' => $shipping['seal_number'] ?? null,
                'customer_name' => $shipping['customer_name'] ?? null,
                'loading_point' => $shipping['loading_point'] ?? null,
                'destination' => $shipping['destination'] ?? null,
                'shipping_line' => $shipping['shipping_line'] ?? null,
                'size' => $shipping['size'] ?? null,
                'loading_date' => $shipping['loading_date'] ?? null,
                'eta' => $shipping['eta'] ?? null,
                'status' => $this->normalizeListStatus($firstRaw),
                'released' => (bool) Arr::get($firstRaw, 'released', false),
                'bol_url' => null,
                'invoice_ref' => null,
                'vehicles' => array_map(function (Vehicle $vehicle) {
                    $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
                    $year = $vehicle->year ?? Arr::get($raw, 'year');
                    $make = $vehicle->make ?? $this->string($raw, 'make');
                    $model = $vehicle->model ?? $this->string($raw, 'model');
                    $vin = $vehicle->vin ?? $this->string($raw, 'vin');
                    $title = trim(implode(' ', array_filter([
                        $year !== null && $year !== '' ? (string) $year : null,
                        $make,
                        $model,
                    ])));

                    return [
                        'vin' => $vin,
                        'year' => $year,
                        'make' => $make,
                        'model' => $model,
                        'title' => $title !== '' ? $title : ($vin ?: '—'),
                    ];
                }, $group),
                'tracking_available' => $this->rowTrackingAvailable([
                    'container_number' => $containerNumber ?? $bookingNumber,
                    'loading_point' => $shipping['loading_point'] ?? null,
                    'destination' => $shipping['destination'] ?? null,
                ]),
                'source' => 'vehicles',
            ];
        }

        usort($containers, fn ($a, $b) => strcmp(
            (string) ($b['loading_date'] ?? ''),
            (string) ($a['loading_date'] ?? ''),
        ));

        return $containers;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function normalizeListStatus(array $item): string
    {
        if ((bool) (Arr::get($item, 'released') ?? Arr::get($item, 'is_released'))) {
            return 'released';
        }

        $raw = strtolower($this->string($item, 'status') ?? '');

        if (in_array($raw, ['released', 'delivered'], true)) {
            return 'released';
        }

        if ($raw === 'arrived') {
            return 'arrived';
        }

        if ($raw === 'loading') {
            return 'loading';
        }

        if (in_array($raw, ['pending', 'in_transit', ''], true)) {
            return 'in_transit';
        }

        return 'in_transit';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function bolUrl(array $item): ?string
    {
        foreach (['bol_url', 'bol_link', 'bol'] as $key) {
            $value = Arr::get($item, $key);

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
        }

        $documents = Arr::get($item, 'documents', Arr::get($item, 'bol_documents', []));

        if (! is_array($documents)) {
            return null;
        }

        foreach ($documents as $document) {
            if (! is_array($document)) {
                continue;
            }

            $type = strtolower((string) Arr::get($document, 'type', Arr::get($document, 'name', '')));

            if (! str_contains($type, 'bol') && ! str_contains($type, 'lading')) {
                continue;
            }

            $url = Arr::get($document, 'url', Arr::get($document, 'link'));

            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function invoiceRef(array $item): ?string
    {
        foreach (['invoice_number', 'invoice_ref', 'invoice_id'] as $key) {
            $value = Arr::get($item, $key);

            if ($value !== null && $value !== '') {
                return $this->scalarString($value);
            }
        }

        $invoices = Arr::get($item, 'invoices', []);

        if (! is_array($invoices) || $invoices === []) {
            return null;
        }

        $first = $invoices[0];

        if (! is_array($first)) {
            return $this->scalarString($first);
        }

        return $this->string($first, 'invoice_number')
            ?: $this->string($first, 'number')
            ?: (Arr::get($first, 'id') !== null ? (string) Arr::get($first, 'id') : null);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function rowTrackingAvailable(array $item): bool
    {
        $number = trim($this->scalarString($item['container_number'] ?? null) ?? '');

        if ($number === '') {
            return false;
        }

        $from = trim($this->scalarString($item['loading_point'] ?? null) ?? '');
        $to = trim($this->scalarString($item['destination'] ?? null) ?? '');

        return $from !== '' || $to !== '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function string(array $item, string $key): ?string
    {
        return $this->scalarString(Arr::get($item, $key));
    }

    protected function scalarString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return trim((string) $value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (! is_array($value)) {
            return null;
        }

        if ($value === []) {
            return null;
        }

        foreach (['name', 'label', 'title', 'value', 'text', 'port', 'city'] as $key) {
            if (array_key_exists($key, $value)) {
                $nested = $this->scalarString($value[$key]);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        $parts = [];

        foreach ($value as $item) {
            if (is_scalar($item) && $item !== '') {
                $parts[] = trim((string) $item);
            } elseif (is_array($item)) {
                $nested = $this->scalarString($item);

                if ($nested !== null) {
                    $parts[] = $nested;
                }
            }
        }

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

        return is_string($encoded) && $encoded !== '[]' && $encoded !== '{}' ? $encoded : null;
    }

    /**
     * Attach image_count and thumbnail_url to each list row (single batched query).
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function attachListImageSummaries(array $items): array
    {
        if ($items === []) {
            return $items;
        }

        /** @var array<string, string> $bookingToContainer */
        $bookingToContainer = [];
        /** @var array<string, string> $containerToBooking */
        $containerToBooking = [];

        foreach ($items as $row) {
            $container = $this->normalizeContainerNumber($row['container_number'] ?? null);
            $booking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

            if ($container !== null && $container !== '' && $booking !== null && $booking !== '') {
                $bookingToContainer[$booking] = $container;
                $containerToBooking[$container] = $booking;
            }
        }

        /** @var array<int, list<string>> $rowLookupKeys */
        $rowLookupKeys = [];
        $allKeys = [];

        foreach ($items as $index => $row) {
            $keys = [];
            $container = $this->normalizeContainerNumber($row['container_number'] ?? null);
            $booking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

            if ($container !== null && $container !== '') {
                $keys[] = $container;

                if (isset($containerToBooking[$container])) {
                    $keys[] = $containerToBooking[$container];
                }
            }

            if ($booking !== null && $booking !== '') {
                $keys[] = $booking;

                if (isset($bookingToContainer[$booking])) {
                    $keys[] = $bookingToContainer[$booking];
                }
            }

            $keys = array_values(array_unique($keys));
            $rowLookupKeys[$index] = $keys;

            foreach ($keys as $key) {
                $allKeys[$key] = true;
            }
        }

        if ($allKeys === []) {
            return array_map(function (array $row): array {
                $row['image_count'] = 0;
                $row['thumbnail_url'] = null;

                return $row;
            }, $items);
        }

        $imagesByKey = ContainerImage::query()
            ->whereIn('container_number', array_keys($allKeys))
            ->orderBy('id')
            ->get()
            ->groupBy('container_number');

        $enriched = [];

        foreach ($items as $index => $row) {
            $imageCount = 0;
            $thumbnailUrl = null;

            foreach ($rowLookupKeys[$index] ?? [] as $key) {
                $records = $imagesByKey->get($key);

                if ($records === null || $records->isEmpty()) {
                    continue;
                }

                $imageCount = $records->count();
                $thumbnailUrl = $records->first()->cloudinary_url;
                break;
            }

            $row['image_count'] = $imageCount;
            $row['thumbnail_url'] = $thumbnailUrl;
            $enriched[] = $row;
        }

        return $enriched;
    }

    /**
     * @param  list<array<string, mixed>>  $fromApi
     * @param  list<array<string, mixed>>  $fromVehicles
     * @return list<array<string, mixed>>
     */
    protected function mergeDealerContainerLists(array $fromApi, array $fromVehicles): array
    {
        /** @var array<string, array<string, mixed>> $byKey */
        $byKey = [];

        foreach ($fromApi as $row) {
            $key = $this->containerListKey($row);

            if ($key === '') {
                continue;
            }

            $byKey[$key] = $row;
        }

        foreach ($fromVehicles as $row) {
            $key = $this->containerListKey($row);

            if ($key === '') {
                continue;
            }

            if (! isset($byKey[$key])) {
                $byKey[$key] = $row;
            }
        }

        $merged = array_values($byKey);

        usort($merged, fn ($a, $b) => strcmp(
            (string) ($b['loading_date'] ?? ''),
            (string) ($a['loading_date'] ?? ''),
        ));

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function containerListKey(array $row): string
    {
        $container = $this->normalizeContainerNumber($row['container_number'] ?? null);

        if ($container !== null && $container !== '') {
            return 'cn:'.$container;
        }

        $booking = $this->normalizeBookingNumber($row['booking_number'] ?? null);

        if ($booking !== null && $booking !== '') {
            return 'bk:'.$booking;
        }

        return '';
    }

    protected function normalizeVin(string $vin): string
    {
        return strtoupper(trim($vin));
    }

    protected function normalizeContainerNumber(mixed $value): ?string
    {
        $stringValue = $this->scalarString($value);

        if ($stringValue === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $stringValue) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    protected function normalizeBookingNumber(mixed $value): ?string
    {
        $stringValue = $this->scalarString($value);

        if ($stringValue === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $stringValue) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     container_number: ?string,
     *     booking_number: ?string,
     *     seal_number: ?string,
     *     customer_name: ?string,
     *     loading_point: ?string,
     *     destination: ?string,
     *     shipping_line: ?string,
     *     size: ?string,
     *     loading_date: ?string,
     *     eta: ?string,
     *     vin: ?string,
     * }
     */
    protected function extractShippingFromRaw(array $raw): array
    {
        $nested = Arr::get($raw, 'shipping', Arr::get($raw, 'shipment', []));
        $containerObj = Arr::get($raw, 'container', []);

        if (! is_array($nested)) {
            $nested = [];
        }

        if (! is_array($containerObj)) {
            $containerObj = [];
        }

        $containerNumber = $this->string($raw, 'container_number')
            ?: $this->string($nested, 'container_number')
            ?: $this->string($containerObj, 'number')
            ?: $this->string($containerObj, 'container_number');

        $bookingNumber = $this->string($raw, 'booking_number')
            ?: $this->string($nested, 'booking_number')
            ?: $this->string($containerObj, 'booking_number');

        return [
            'container_number' => $containerNumber,
            'booking_number' => $bookingNumber,
            'seal_number' => $this->string($raw, 'seal_number') ?: $this->string($raw, 'seal'),
            'customer_name' => $this->string($raw, 'buyer')
                ?: $this->string($raw, 'customer_name')
                ?: $this->string($raw, 'customer'),
            'loading_point' => $this->string($raw, 'loading_point') ?: $this->string($nested, 'loading_point'),
            'destination' => $this->string($raw, 'destination') ?: $this->string($nested, 'destination'),
            'shipping_line' => $this->string($raw, 'shipping_line') ?: $this->string($nested, 'shipping_line'),
            'size' => $this->string($raw, 'size'),
            'loading_date' => $this->string($raw, 'loading_date'),
            'eta' => $this->string($raw, 'eta')
                ?: $this->string($raw, 'estimated_arrival')
                ?: $this->string($raw, 'eta_date'),
            'vin' => $this->string($raw, 'vin'),
        ];
    }

    /**
     * @param  array{vins: list<string>, container_numbers: list<string>, booking_numbers: list<string>}  $keys
     * @param  list<array<string, mixed>>  $apiItems
     * @param  list<array<string, mixed>>  $fromApi
     * @param  list<array<string, mixed>>  $fromVehicles
     * @param  list<array<string, mixed>>  $merged
     */
    protected function logDealerContainerEdgeCases(
        Dealer $dealer,
        array $keys,
        array $apiItems,
        array $fromApi,
        array $fromVehicles,
        array $merged,
    ): void {
        if (! config('vinstack.log_container_matching', false)) {
            return;
        }

        $hasKeys = $keys['vins'] !== [] || $keys['container_numbers'] !== [] || $keys['booking_numbers'] !== [];

        if ($hasKeys && $fromApi === [] && $fromVehicles !== []) {
            Log::debug('dealer.containers: vehicle-derived rows (no Vinstack API match)', [
                'dealer_id' => $dealer->id,
                'derived_count' => count($fromVehicles),
                'dealer_container_numbers' => $keys['container_numbers'],
                'dealer_booking_numbers' => $keys['booking_numbers'],
            ]);
        }

        if ($hasKeys && $fromApi !== [] && $fromVehicles !== []) {
            $apiKeys = array_map(fn (array $row) => $this->containerListKey($row), $fromApi);
            $derivedOnly = array_values(array_filter(
                $fromVehicles,
                fn (array $row) => ! in_array($this->containerListKey($row), $apiKeys, true),
            ));

            if ($derivedOnly !== []) {
                Log::debug('dealer.containers: merged vehicle-derived rows missing from API filter', [
                    'dealer_id' => $dealer->id,
                    'added_count' => count($derivedOnly),
                    'keys' => array_map(fn (array $row) => $this->containerListKey($row), $derivedOnly),
                ]);
            }
        }

        if (! $hasKeys || $merged !== []) {
            return;
        }

        Log::debug('dealer.containers: assigned vehicles have no container/booking/VIN keys', [
            'dealer_id' => $dealer->id,
        ]);

        if ($apiItems === []) {
            return;
        }

        $apiNumbers = array_values(array_filter(array_map(
            fn (array $row) => $this->normalizeContainerNumber($row['container_number'] ?? null),
            $apiItems,
        )));

        Log::debug('dealer.containers: Vinstack list returned but no row matched dealer keys', [
            'dealer_id' => $dealer->id,
            'dealer_container_numbers' => $keys['container_numbers'],
            'dealer_booking_numbers' => $keys['booking_numbers'],
            'dealer_vins' => $keys['vins'],
            'vinstack_container_numbers_sample' => array_slice($apiNumbers, 0, 10),
        ]);
    }
}
