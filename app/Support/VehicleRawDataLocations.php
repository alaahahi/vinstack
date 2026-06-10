<?php

namespace App\Support;

use Illuminate\Support\Arr;

class VehicleRawDataLocations
{
    /** @var list<string> */
    public const ORIGIN_FALLBACK_KEYS = ['pol', 'prepol'];

    /** @var list<string> */
    public const DESTINATION_FALLBACK_KEYS = ['postpod', 'pod', 'shipping_destination'];

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function sanitizeForList(array $raw): array
    {
        $sanitized = $raw;

        $origin = self::resolveOrigin($raw);

        if ($origin !== null) {
            $sanitized['loading_point'] = $origin;
        } elseif (self::shouldClearLocation(Arr::get($raw, 'loading_point'))) {
            unset($sanitized['loading_point']);
        }

        $destination = self::resolveDestination($raw);

        if ($destination !== null) {
            $sanitized['destination'] = $destination;
        } elseif (self::shouldClearLocation(Arr::get($raw, 'destination'))) {
            unset($sanitized['destination']);
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function resolveOrigin(array $raw): ?string
    {
        return self::resolveLocation($raw, 'loading_point', self::ORIGIN_FALLBACK_KEYS);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function resolveDestination(array $raw): ?string
    {
        return self::resolveLocation($raw, 'destination', self::DESTINATION_FALLBACK_KEYS);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $fallbackKeys
     */
    protected static function resolveLocation(array $raw, string $primaryKey, array $fallbackKeys): ?string
    {
        $keys = [$primaryKey, ...$fallbackKeys];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $raw)) {
                continue;
            }

            $label = self::locationLabel($raw[$key]);

            if ($label !== null) {
                return $label;
            }
        }

        return null;
    }

    public static function shouldClearLocation(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (self::isGalleryStageBlock($value)) {
            return true;
        }

        if (is_string($value) && self::isImageLikeString($value)) {
            return true;
        }

        if (is_array($value)) {
            return self::locationLabel($value) === null;
        }

        return false;
    }

    public static function isGalleryStageBlock(mixed $value): bool
    {
        return is_array($value)
            && isset($value['urls'])
            && is_array($value['urls']);
    }

    public static function isImageLikeString(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/(?:^https?:\/\/|\/autos\/|\/storage\/|\.(?:jpe?g|png|gif|webp|bmp|svg)(?:\?|$|[#&]))/i', $trimmed)) {
            return true;
        }

        if (preg_match('/(?:^|[\s,\/])images-\d{10,13}(?:-|\.|$)/i', $trimmed)) {
            return true;
        }

        if (preg_match('/^(?:images-\d{10,13}-[^\s,.]+)(?:[\s,]+images-\d{10,13}-[^\s,.]+)+$/i', $trimmed)) {
            return true;
        }

        return (bool) preg_match('/^(?:images-\d{10,13}-[^\s,.]+)(?:\.images-\d{10,13}-[^\s,.]+)+$/i', $trimmed);
    }

    public static function locationLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (self::isGalleryStageBlock($value)) {
            foreach (['name', 'label', 'title', 'port', 'city', 'value', 'text'] as $key) {
                if (! array_key_exists($key, $value)) {
                    continue;
                }

                $nested = self::locationLabel($value[$key]);

                if ($nested !== null) {
                    return $nested;
                }
            }

            return null;
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            $trimmed = trim((string) $value);

            if ($trimmed === '' || self::isImageLikeString($trimmed)) {
                return null;
            }

            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                return null;
            }

            return $trimmed;
        }

        if (! is_array($value)) {
            return null;
        }

        foreach (['name', 'label', 'title', 'port', 'city', 'value', 'text'] as $key) {
            if (! array_key_exists($key, $value)) {
                continue;
            }

            $nested = self::locationLabel($value[$key]);

            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
