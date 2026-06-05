<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContainerService
{
    public function __construct(
        protected VinstackService $vinstack,
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
            $items = $this->fetchNormalized();
        }

        if ($chassis !== null && trim($chassis) !== '') {
            $needle = strtoupper(trim($chassis));
            $items = array_values(array_filter(
                $items,
                fn (array $container) => $this->containerMatchesChassis($container, $needle),
            ));
        }

        return $items;
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

        return $merged;
    }

    public function trackingAvailable(): bool
    {
        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchNormalized(): array
    {
        try {
            $raw = $this->vinstack->containers();
        } catch (RuntimeException) {
            return [];
        }

        return array_values(array_map(
            fn (array $item) => $this->normalize($item),
            array_filter($raw, fn ($item) => is_array($item)),
        ));
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
     * Build container rows from synced vehicle raw_data when Vinstack list is empty or unmatched.
     *
     * @return list<array<string, mixed>>
     */
    protected function deriveFromDealerVehicles(Dealer $dealer): array
    {
        $vehicles = Vehicle::query()
            ->whereHas('assignments', function ($q) use ($dealer) {
                $q->where('dealer_id', $dealer->id)->where('is_active', true);
            })
            ->get();

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

        foreach ($groups as $groupKey => $group) {
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
                return (string) $value;
            }
        }

        $invoices = Arr::get($item, 'invoices', []);

        if (! is_array($invoices) || $invoices === []) {
            return null;
        }

        $first = $invoices[0];

        if (! is_array($first)) {
            return (string) $first;
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
        $number = trim((string) ($item['container_number'] ?? ''));

        if ($number === '') {
            return false;
        }

        $from = trim((string) ($item['loading_point'] ?? ''));
        $to = trim((string) ($item['destination'] ?? ''));

        return $from !== '' || $to !== '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function string(array $item, string $key): ?string
    {
        $value = Arr::get($item, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
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
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim((string) $value)) ?? '');

        return $normalized !== '' ? $normalized : null;
    }

    protected function normalizeBookingNumber(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim((string) $value)) ?? '');

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
