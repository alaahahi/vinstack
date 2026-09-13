<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class DealerPresence
{
    public static function thresholdMinutes(): int
    {
        return (int) config('presence.online_threshold_minutes', 5);
    }

    public static function touchThrottleSeconds(): int
    {
        return (int) config('presence.touch_throttle_seconds', 90);
    }

    public static function isOnline(User $user): bool
    {
        if (! $user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->gte(
            now()->subMinutes(self::thresholdMinutes()),
        );
    }

    /**
     * Best-effort presence write. Must never throw — SQLite lock contention
     * under concurrent PHP-FPM / queue writers must not 500 heartbeat or login.
     */
    public static function touch(User $user): void
    {
        if (self::shouldSkipTouch($user)) {
            return;
        }

        $now = now();

        try {
            // Narrow query-builder update (no model events / no updated_at) to
            // keep the write short under SQLite write locks.
            $user->newQuery()
                ->whereKey($user->getKey())
                ->toBase()
                ->update(['last_seen_at' => $now]);

            $user->forceFill(['last_seen_at' => $now]);
            $user->syncOriginalAttribute('last_seen_at');
        } catch (QueryException|PDOException) {
            // Intentionally swallow — presence is non-critical.
        }
    }

    protected static function shouldSkipTouch(User $user): bool
    {
        $throttle = self::touchThrottleSeconds();

        if ($throttle <= 0 || ! $user->last_seen_at) {
            return false;
        }

        try {
            return $user->last_seen_at->greaterThan(now()->subSeconds($throttle));
        } catch (Throwable) {
            return false;
        }
    }
}
