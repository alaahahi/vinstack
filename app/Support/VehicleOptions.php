<?php

namespace App\Support;

use Illuminate\Support\Arr;

class VehicleOptions
{
    public const KEYS = [
        'shipping_destinations',
        'loading_points',
        'auctions',
        'shipping_methods',
        'delivery_types',
        'title_types',
    ];

    /**
     * @return array<string, list<string>>
     */
    public static function defaults(): array
    {
        return [
            'shipping_destinations' => ['Dubai', 'Mersin', 'Jebel Ali'],
            'loading_points' => ['New York', 'Savannah', 'Houston'],
            'auctions' => ['Copart', 'IAAI', 'Manheim'],
            'shipping_methods' => ['RoRo', 'Container'],
            'delivery_types' => ['Door', 'Port'],
            'title_types' => ['Clean', 'Salvage'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, list<string>>
     */
    public static function resolve(?array $stored = null): array
    {
        $defaults = self::defaults();
        $stored = is_array($stored) ? $stored : [];
        $resolved = [];

        foreach (self::KEYS as $key) {
            $items = Arr::get($stored, $key, Arr::get($defaults, $key, []));
            $resolved[$key] = self::normalizeList($items);
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    public static function normalizeList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            $value = trim((string) $item);

            if ($value !== '' && ! in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
