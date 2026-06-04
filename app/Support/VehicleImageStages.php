<?php

namespace App\Support;

use Illuminate\Support\Arr;

class VehicleImageStages
{
    public const STAGES = ['terminal', 'pickup', 'destination'];

    /**
     * @param  array<string, mixed>  $source  Vinstack auto payload or vehicle raw_data
     * @return array{terminal: list<string>, pickup: list<string>, destination: list<string>}
     */
    public static function resolve(array $source): array
    {
        $thumbnail = self::normalizeUrl(Arr::get($source, 'thumbnail_url'));
        $stages = [
            'terminal' => [],
            'pickup' => [],
            'destination' => [],
        ];

        self::collectNamedArrays($stages, $source, $thumbnail);

        $nested = Arr::get($source, 'photos', Arr::get($source, 'gallery', []));
        if (is_array($nested)) {
            self::collectNestedObject($stages, $nested, $thumbnail);
        }

        $unclassified = [];

        foreach ([Arr::get($source, 'images', []), Arr::get($source, 'image_urls', [])] as $list) {
            if (! is_array($list)) {
                continue;
            }

            foreach ($list as $image) {
                if (is_string($image)) {
                    $normalized = self::normalizeUrl($image);

                    if (! $normalized || $normalized === $thumbnail || self::containsUrl($stages, $normalized)) {
                        continue;
                    }

                    $stage = self::classifyUrl($normalized);

                    if ($stage !== null) {
                        self::pushUrl($stages[$stage], $normalized, null);
                    } else {
                        $unclassified[] = $normalized;
                    }

                    continue;
                }

                if (! is_array($image)) {
                    continue;
                }

                $url = $image['url'] ?? $image['src'] ?? $image['path'] ?? null;
                $stage = self::normalizeStage(
                    $image['stage'] ?? $image['type'] ?? $image['location'] ?? $image['phase'] ?? null,
                );

                if (! is_string($url) || $url === '') {
                    continue;
                }

                $normalized = self::normalizeUrl($url);

                if (! $normalized || $normalized === $thumbnail || self::containsUrl($stages, $normalized)) {
                    continue;
                }

                if ($stage !== null) {
                    self::pushUrl($stages[$stage], $normalized, null);
                } else {
                    $unclassified[] = $normalized;
                }
            }
        }

        $remaining = array_values(array_filter(
            $unclassified,
            fn (string $url) => ! self::containsUrl($stages, $url),
        ));

        self::assignBatchedFlatImages($stages, $remaining);

        foreach (self::STAGES as $stage) {
            $stages[$stage] = array_values(array_unique($stages[$stage]));
        }

        return $stages;
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     * @param  array<string, mixed>  $source
     */
    protected static function collectNamedArrays(array &$stages, array $source, ?string $thumbnail): void
    {
        $map = [
            'terminal' => ['terminal_images', 'images_terminal', 'terminal_photos'],
            'pickup' => ['pickup_images', 'images_pickup', 'pickup_photos'],
            'destination' => ['destination_images', 'images_destination', 'destination_photos', 'delivery_images'],
        ];

        foreach ($map as $stage => $keys) {
            foreach ($keys as $key) {
                $list = Arr::get($source, $key);
                if (is_array($list)) {
                    self::collectFlatList($stages[$stage], $list, $thumbnail);
                }
            }
        }
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     * @param  array<string, mixed>  $nested
     */
    protected static function collectNestedObject(array &$stages, array $nested, ?string $thumbnail): void
    {
        foreach (self::STAGES as $stage) {
            $list = Arr::get($nested, $stage);
            if (is_array($list)) {
                self::collectFlatList($stages[$stage], $list, $thumbnail);
            }
        }
    }

    /**
     * @param  list<string>  $target
     * @param  mixed  $list
     */
    protected static function collectFlatList(array &$target, mixed $list, ?string $thumbnail): void
    {
        if (! is_array($list)) {
            return;
        }

        foreach ($list as $image) {
            if (is_string($image)) {
                self::pushUrl($target, $image, $thumbnail);

                continue;
            }

            if (! is_array($image)) {
                continue;
            }

            $url = $image['url'] ?? $image['src'] ?? $image['path'] ?? null;
            $stage = self::normalizeStage(
                $image['stage'] ?? $image['type'] ?? $image['location'] ?? $image['phase'] ?? null,
            );

            if (is_string($url) && $url !== '' && $stage === null) {
                self::pushUrl($target, $url, $thumbnail);
            }
        }
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     * @param  list<string>  $urls
     */
    protected static function assignBatchedFlatImages(array &$stages, array $urls): void
    {
        if ($urls === []) {
            return;
        }

        $batches = [];

        foreach ($urls as $url) {
            if (preg_match('/images-(\d{10,13})-/i', $url, $matches)) {
                $batches[$matches[1]][] = $url;
            } else {
                $batches['other'][] = $url;
            }
        }

        $keys = array_keys($batches);
        usort($keys, function (string $a, string $b): int {
            if ($a === 'other') {
                return 1;
            }
            if ($b === 'other') {
                return -1;
            }

            return $a <=> $b;
        });

        $order = self::STAGES;

        foreach ($keys as $index => $key) {
            $stage = $order[min($index, count($order) - 1)] ?? 'terminal';
            foreach ($batches[$key] as $url) {
                self::pushUrl($stages[$stage], $url, null);
            }
        }
    }

    protected static function classifyUrl(string $url): ?string
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'pickup')) {
            return 'pickup';
        }

        if (
            str_contains($lower, 'destination')
            || str_contains($lower, 'delivery')
            || str_contains($lower, 'dropoff')
        ) {
            return 'destination';
        }

        if (str_contains($lower, '/autos/') || str_contains($lower, 'terminal')) {
            return 'terminal';
        }

        return null;
    }

    protected static function normalizeStage(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        if (in_array($normalized, self::STAGES, true)) {
            return $normalized;
        }

        if (str_contains($normalized, 'pickup')) {
            return 'pickup';
        }

        if (str_contains($normalized, 'destination') || str_contains($normalized, 'delivery')) {
            return 'destination';
        }

        if (str_contains($normalized, 'terminal')) {
            return 'terminal';
        }

        return null;
    }

    /**
     * @param  list<string>  $target
     */
    protected static function pushUrl(array &$target, string $url, ?string $exclude): void
    {
        $normalized = self::normalizeUrl($url);

        if (! $normalized || $normalized === $exclude || in_array($normalized, $target, true)) {
            return;
        }

        $target[] = $normalized;
    }

    protected static function normalizeUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        if (str_contains($url, 'no_photo.png') || str_contains($url, 'no_photo')) {
            return null;
        }

        if (preg_match('/(?:^|\/)thumbnail[-_]/i', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * @param  array{terminal: list<string>, pickup: list<string>, destination: list<string>}  $stages
     */
    protected static function containsUrl(array $stages, string $url): bool
    {
        foreach (self::STAGES as $stage) {
            if (in_array($url, $stages[$stage], true)) {
                return true;
            }
        }

        return false;
    }
}
