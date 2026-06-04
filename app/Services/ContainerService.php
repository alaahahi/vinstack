<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
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
        return $this->fetchNormalized();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForDealer(Dealer $dealer): array
    {
        $keys = $this->dealerMatchKeys($dealer);
        $items = $this->fetchNormalized();

        $filtered = array_values(array_filter(
            $items,
            fn (array $container) => $this->matchesDealer($container, $keys),
        ));

        if ($filtered !== []) {
            return $filtered;
        }

        return $this->deriveFromDealerVehicles($dealer);
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
            $vin = strtoupper(trim((string) $vehicle->vin));

            if ($vin !== '') {
                $vins[] = $vin;
            }

            $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

            $container = strtoupper(trim((string) Arr::get($raw, 'container_number', '')));

            if ($container !== '') {
                $containerNumbers[] = $container;
            }

            $booking = strtoupper(trim((string) Arr::get($raw, 'booking_number', '')));

            if ($booking !== '') {
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
        $containerNumber = strtoupper(trim((string) ($container['container_number'] ?? '')));
        $bookingNumber = strtoupper(trim((string) ($container['booking_number'] ?? '')));

        if ($containerNumber !== '' && in_array($containerNumber, $keys['container_numbers'], true)) {
            return true;
        }

        if ($bookingNumber !== '' && in_array($bookingNumber, $keys['booking_numbers'], true)) {
            return true;
        }

        foreach ($container['vehicles'] ?? [] as $vehicle) {
            $vin = strtoupper(trim((string) ($vehicle['vin'] ?? '')));

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
            $containerNumber = trim((string) Arr::get($raw, 'container_number', ''));

            if ($containerNumber === '') {
                continue;
            }

            $groups[$containerNumber][] = $vehicle;
        }

        $containers = [];

        foreach ($groups as $containerNumber => $group) {
            $firstRaw = is_array($group[0]->raw_data) ? $group[0]->raw_data : [];

            $containers[] = [
                'id' => null,
                'container_number' => $containerNumber,
                'booking_number' => $this->string($firstRaw, 'booking_number'),
                'seal_number' => $this->string($firstRaw, 'seal_number') ?: $this->string($firstRaw, 'seal'),
                'customer_name' => $this->string($firstRaw, 'buyer')
                    ?: $this->string($firstRaw, 'customer_name'),
                'loading_point' => $this->string($firstRaw, 'loading_point'),
                'destination' => $this->string($firstRaw, 'destination'),
                'shipping_line' => $this->string($firstRaw, 'shipping_line'),
                'size' => $this->string($firstRaw, 'size'),
                'loading_date' => $this->string($firstRaw, 'loading_date'),
                'eta' => $this->string($firstRaw, 'eta')
                    ?: $this->string($firstRaw, 'estimated_arrival')
                    ?: $this->string($firstRaw, 'eta_date'),
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
                    'container_number' => $containerNumber,
                    'loading_point' => $this->string($firstRaw, 'loading_point'),
                    'destination' => $this->string($firstRaw, 'destination'),
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
}
