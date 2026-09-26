<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Coordinates the scheduled writers around SQLite's single write lock.
 *
 * `withoutOverlapping()` only stops a command overlapping itself, so the
 * hourly syncs and the per-minute image-transfer worker could still contend.
 * A sync claims this window while it runs; the image-transfer worker skips
 * those minutes and picks up again on its next tick.
 */
final class SyncWriterWindow
{
    private const KEY = 'sync:writer-active';

    /**
     * Bounds the marker if a sync is killed mid-run. A sync normally finishes
     * in seconds, so this only ever matters as a safety net.
     */
    private const TTL_SECONDS = 600;

    public static function open(string $owner): void
    {
        Cache::put(self::KEY, $owner, self::TTL_SECONDS);
    }

    public static function close(): void
    {
        Cache::forget(self::KEY);
    }

    public static function isOpen(): bool
    {
        return Cache::has(self::KEY);
    }
}
