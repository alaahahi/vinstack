<?php

namespace App\Services;

use App\Models\Dealer;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ContainerTrackingService
{
    public const CACHE_TTL_SECONDS = 86400;

    public function __construct(
        protected VinstackService $vinstack,
        protected ContainerService $containers,
        protected PortGeocoderService $geocoder,
    ) {}

    public function trackingAvailable(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function forAdmin(string $containerNumber): array
    {
        $container = $this->findInList(
            $this->containers->listForAdmin(),
            $containerNumber,
        );

        if ($container === null) {
            $container = $this->fetchVinstackContainerFallback($containerNumber);
        }

        return $this->resolve($containerNumber, $container);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchVinstackContainerFallback(string $containerNumber): ?array
    {
        try {
            $raw = $this->vinstack->container($containerNumber);
        } catch (RuntimeException) {
            return null;
        }

        return [
            'container_number' => $raw['container_number'] ?? $containerNumber,
            'booking_number' => $raw['booking_number'] ?? null,
            'loading_point' => $raw['loading_point'] ?? null,
            'destination' => $raw['destination'] ?? null,
            'shipping_line' => $raw['shipping_line'] ?? null,
            'loading_date' => $raw['loading_date'] ?? null,
            'eta' => $raw['eta'] ?? $raw['estimated_arrival'] ?? null,
            'released' => (bool) ($raw['released'] ?? $raw['is_released'] ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forDealer(Dealer $dealer, string $containerNumber): array
    {
        $container = $this->findInList(
            $this->containers->listForDealer($dealer),
            $containerNumber,
        );

        if ($container === null) {
            abort(404, 'Container not found for this dealer.');
        }

        return $this->resolve($containerNumber, $container);
    }

    /**
     * @param  list<array<string, mixed>>  $list
     * @return array<string, mixed>|null
     */
    protected function findInList(array $list, string $containerNumber): ?array
    {
        $needle = strtoupper(trim($containerNumber));

        foreach ($list as $item) {
            $number = strtoupper(trim((string) ($item['container_number'] ?? '')));

            if ($number !== '' && $number === $needle) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $container
     * @return array<string, mixed>
     */
    protected function resolve(string $containerNumber, ?array $container): array
    {
        $cacheKey = 'container_tracking:'.md5(strtoupper(trim($containerNumber)));

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $cached['cached'] = true;
            $cached['cache_note'] = 'مخزّن لمدة 24 ساعة';

            return $cached;
        }

        $payload = $this->build($containerNumber, $container);
        $payload['cached'] = false;
        $payload['cache_note'] = null;
        $payload['cached_at'] = now()->toIso8601String();
        $payload['expires_at'] = now()->addSeconds(self::CACHE_TTL_SECONDS)->toIso8601String();

        Cache::put($cacheKey, $payload, self::CACHE_TTL_SECONDS);

        return $payload;
    }

    /**
     * @param  array<string, mixed>|null  $container
     * @return array<string, mixed>
     */
    protected function build(string $containerNumber, ?array $container): array
    {
        $vinstackTracking = null;

        try {
            $vinstackTracking = $this->vinstack->containerTracking($containerNumber);
        } catch (RuntimeException) {
            $vinstackTracking = null;
        }

        if (is_array($vinstackTracking) && $vinstackTracking !== []) {
            return $this->normalizeVinstackTracking($containerNumber, $container, $vinstackTracking);
        }

        if ($container === null) {
            abort(404, 'Container not found.');
        }

        return $this->buildDerived($containerNumber, $container);
    }

    /**
     * @param  array<string, mixed>|null  $container
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function normalizeVinstackTracking(
        string $containerNumber,
        ?array $container,
        array $raw,
    ): array {
        $events = [];
        $rawEvents = Arr::get($raw, 'events', Arr::get($raw, 'milestones', []));

        if (is_array($rawEvents)) {
            foreach ($rawEvents as $event) {
                if (! is_array($event)) {
                    continue;
                }

                $events[] = [
                    'date' => $this->stringFrom($event, 'date')
                        ?: $this->stringFrom($event, 'occurred_at')
                        ?: $this->stringFrom($event, 'timestamp'),
                    'title' => $this->stringFrom($event, 'title')
                        ?: $this->stringFrom($event, 'status')
                        ?: $this->stringFrom($event, 'description')
                        ?: '—',
                    'location' => $this->stringFrom($event, 'location')
                        ?: $this->stringFrom($event, 'port'),
                    'type' => $this->stringFrom($event, 'type') ?: 'event',
                ];
            }
        }

        $originName = $this->stringFrom($raw, 'origin')
            ?: ($container['loading_point'] ?? null);
        $destName = $this->stringFrom($raw, 'destination')
            ?: ($container['destination'] ?? null);

        $origin = $this->locationFromRaw(Arr::get($raw, 'origin_coords', Arr::get($raw, 'origin')), $originName);
        $destination = $this->locationFromRaw(Arr::get($raw, 'destination_coords', Arr::get($raw, 'destination')), $destName);

        $waypoints = $this->waypointsFromRaw(Arr::get($raw, 'waypoints', Arr::get($raw, 'transshipments', [])));

        $route = Arr::get($raw, 'route', Arr::get($raw, 'path', []));

        if (! is_array($route) || $route === []) {
            $route = $this->buildRoutePolyline($origin, $destination, $waypoints);
        }

        return [
            'source' => 'vinstack',
            'disclaimer' => null,
            'container_number' => $containerNumber,
            'booking_number' => $container['booking_number'] ?? $this->stringFrom($raw, 'booking_number'),
            'carrier' => $this->carrierLabel(
                $this->stringFrom($raw, 'carrier')
                    ?: $this->stringFrom($raw, 'shipping_line')
                    ?: ($container['shipping_line'] ?? null),
            ),
            'status' => $this->stringFrom($raw, 'status') ?: 'in_transit',
            'status_label' => $this->statusLabel($this->stringFrom($raw, 'status') ?: 'in_transit'),
            'eta' => $this->stringFrom($raw, 'eta') ?: ($container['eta'] ?? null),
            'origin' => $origin,
            'destination' => $destination,
            'waypoints' => $waypoints,
            'route' => $route,
            'events' => $events,
        ];
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>
     */
    protected function buildDerived(string $containerNumber, array $container): array
    {
        $originName = trim((string) ($container['loading_point'] ?? '')) ?: null;
        $destName = trim((string) ($container['destination'] ?? '')) ?: null;

        $origin = $originName ? $this->geocoder->resolve($originName) : null;
        $destination = $destName ? $this->geocoder->resolve($destName) : null;

        if ($origin !== null) {
            $origin['label'] = $originName;
        }

        if ($destination !== null) {
            $destination['label'] = $destName;
        }

        $waypoints = [];
        $route = $this->buildRoutePolyline($origin, $destination, $waypoints);

        $status = $this->deriveStatus($container);
        $events = $this->deriveEvents($container, $originName, $destName, $status);

        return [
            'source' => 'derived',
            'disclaimer' => 'تتبع تقديري بين الموانئ المعروفة — لا يشمل موقع السفينة المباشر (AIS).',
            'container_number' => $container['container_number'] ?? $containerNumber,
            'booking_number' => $container['booking_number'] ?? null,
            'carrier' => $this->carrierLabel($container['shipping_line'] ?? null),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'eta' => $container['eta'] ?? null,
            'origin' => $origin,
            'destination' => $destination,
            'waypoints' => $waypoints,
            'route' => $route,
            'events' => $events,
        ];
    }

    /**
     * @param  array<string, mixed>  $container
     */
    protected function deriveStatus(array $container): string
    {
        if (! empty($container['released'])) {
            return 'delivered';
        }

        $eta = $this->parseDate($container['eta'] ?? null);
        $loading = $this->parseDate($container['loading_date'] ?? null);
        $now = Carbon::now();

        if ($eta && $eta->isPast()) {
            return 'arrived';
        }

        if ($loading && $loading->isPast()) {
            return 'in_transit';
        }

        if ($loading) {
            return 'loading';
        }

        return 'in_transit';
    }

    /**
     * @return list<array{date: ?string, title: string, location: ?string, type: string}>
     */
    protected function deriveEvents(
        array $container,
        ?string $originName,
        ?string $destName,
        string $status,
    ): array {
        $events = [];

        $loadingDate = $container['loading_date'] ?? null;

        if ($loadingDate) {
            $events[] = [
                'date' => $loadingDate,
                'title' => 'تم التحميل',
                'location' => $originName,
                'type' => 'loaded',
            ];
        }

        if (in_array($status, ['in_transit', 'arrived', 'delivered'], true)) {
            $events[] = [
                'date' => $loadingDate,
                'title' => 'في الطريق',
                'location' => null,
                'type' => 'in_transit',
            ];
        }

        $eta = $container['eta'] ?? null;

        if ($eta) {
            $events[] = [
                'date' => $eta,
                'title' => in_array($status, ['arrived', 'delivered'], true) ? 'وصول متوقع / فعلي' : 'وصول متوقع (ETA)',
                'location' => $destName,
                'type' => 'eta',
            ];
        }

        if (! empty($container['released'])) {
            $events[] = [
                'date' => $eta,
                'title' => 'تم الإفراج',
                'location' => $destName,
                'type' => 'released',
            ];
        }

        return $events;
    }

    /**
     * @param  mixed  $raw
     * @return array{name: string, lat: float, lng: float, label?: string, geocoded?: bool, geocode_confidence?: string, geocode_provider?: string}|null
     */
    protected function locationFromRaw(mixed $raw, ?string $fallbackName): ?array
    {
        if (is_array($raw) && isset($raw['lat'], $raw['lng'])) {
            return [
                'name' => $this->stringFrom($raw, 'name') ?: $fallbackName ?: '—',
                'lat' => (float) $raw['lat'],
                'lng' => (float) $raw['lng'],
                'label' => $fallbackName,
            ];
        }

        $name = is_string($raw) && $raw !== '' ? $raw : $fallbackName;

        if (! $name) {
            return null;
        }

        $resolved = $this->geocoder->resolve($name);

        if ($resolved === null) {
            return null;
        }

        $resolved['label'] = $name;

        return $resolved;
    }

    /**
     * @param  mixed  $raw
     * @return list<array{name: string, lat: float, lng: float, type: string}>
     */
    protected function waypointsFromRaw(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $waypoints = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->stringFrom($item, 'name')
                ?: $this->stringFrom($item, 'port')
                ?: $this->stringFrom($item, 'location');

            if (! $name) {
                continue;
            }

            $coords = isset($item['lat'], $item['lng'])
                ? ['lat' => (float) $item['lat'], 'lng' => (float) $item['lng'], 'name' => $name]
                : $this->geocoder->resolve($name);

            if ($coords === null) {
                continue;
            }

            $waypoints[] = [
                'name' => $coords['name'] ?? $name,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'type' => 'transshipment',
            ];
        }

        return $waypoints;
    }

    /**
     * @param  array{lat: float, lng: float}|null  $origin
     * @param  array{lat: float, lng: float}|null  $destination
     * @param  list<array{lat: float, lng: float}>  $waypoints
     * @return list<array{0: float, 1: float}>
     */
    protected function buildRoutePolyline(?array $origin, ?array $destination, array $waypoints): array
    {
        $points = [];

        foreach ([$origin, ...$waypoints, $destination] as $loc) {
            if (is_array($loc) && isset($loc['lat'], $loc['lng'])) {
                $points[] = [(float) $loc['lat'], (float) $loc['lng']];
            }
        }

        if (count($points) < 2) {
            return [];
        }

        $segments = [];

        for ($i = 0; $i < count($points) - 1; $i++) {
            $segments = array_merge(
                $segments,
                $this->greatCircleSegment($points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1], 24),
            );
        }

        return $segments;
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    protected function greatCircleSegment(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        int $steps = 24,
    ): array {
        $φ1 = deg2rad($lat1);
        $λ1 = deg2rad($lng1);
        $φ2 = deg2rad($lat2);
        $λ2 = deg2rad($lng2);

        $Δ = 2 * asin(min(1, sqrt(
            sin(($φ2 - $φ1) / 2) ** 2
            + cos($φ1) * cos($φ2) * sin(($λ2 - $λ1) / 2) ** 2
        )));

        if ($Δ < 1e-9) {
            return [[$lat1, $lng1], [$lat2, $lng2]];
        }

        $points = [];

        for ($i = 0; $i <= $steps; $i++) {
            $f = $i / $steps;
            $A = sin((1 - $f) * $Δ) / sin($Δ);
            $B = sin($f * $Δ) / sin($Δ);
            $x = $A * cos($φ1) * cos($λ1) + $B * cos($φ2) * cos($λ2);
            $y = $A * cos($φ1) * sin($λ1) + $B * cos($φ2) * sin($λ2);
            $z = $A * sin($φ1) + $B * sin($φ2);
            $φ = atan2($z, sqrt($x ** 2 + $y ** 2));
            $λ = atan2($y, $x);
            $points[] = [rad2deg($φ), rad2deg($λ)];
        }

        return $points;
    }

    protected function carrierLabel(?string $line): ?string
    {
        if ($line === null || trim($line) === '') {
            return null;
        }

        $line = trim($line);

        if (preg_match('/\b(MSC|MAERSK|CMA|COSCO|HAPAG|ONE|EVERGREEN|ZIM|HMM)\b/i', $line, $m)) {
            return strtoupper($m[1]);
        }

        return $line;
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'delivered', 'released' => 'تم الإفراج',
            'arrived' => 'وصل',
            'loading' => 'تحميل',
            'in_transit' => 'في الطريق',
            default => 'في الطريق',
        };
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function stringFrom(array $item, string $key): ?string
    {
        $value = Arr::get($item, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
