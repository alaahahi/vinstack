<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Automatic vehicle sync is limited to the quiet hours so it does not fight
 * the app for SQLite's single write lock during the day.
 *
 * The window is 22:00 inclusive through 06:00 exclusive in Asia/Baghdad
 * (UTC+3, no daylight saving). The app timezone stays UTC; this clock is
 * independent of it.
 */
final class AutoSyncWindow
{
    public const TIMEZONE = 'Asia/Baghdad';

    public const START = '22:00';

    public const END = '06:00';

    public static function isOpen(?CarbonInterface $at = null): bool
    {
        $at = self::asLocal($at);
        $minutes = ($at->hour * 60) + $at->minute;

        return $minutes >= self::startMinutes() || $minutes < self::endMinutes();
    }

    /**
     * @return array{timezone: string, starts_at: string, ends_at: string, open: bool, local_time: string}
     */
    public static function describe(?CarbonInterface $at = null): array
    {
        $at = self::asLocal($at);

        return [
            'timezone' => self::TIMEZONE,
            'starts_at' => self::START,
            'ends_at' => self::END,
            'open' => self::isOpen($at),
            'local_time' => $at->format('H:i'),
        ];
    }

    public static function skipMessage(): string
    {
        return 'Automatic sync runs only between '.self::START.' and '.self::END.' ('.self::TIMEZONE.'). Skipping.';
    }

    private static function asLocal(?CarbonInterface $at): CarbonInterface
    {
        $at ??= Carbon::now();

        return $at->copy()->timezone(self::TIMEZONE);
    }

    private static function startMinutes(): int
    {
        return 22 * 60;
    }

    private static function endMinutes(): int
    {
        return 6 * 60;
    }
}
