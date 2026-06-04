<?php

namespace App\Support;

use App\Models\User;

class DealerPresence
{
    public static function thresholdMinutes(): int
    {
        return (int) config('presence.online_threshold_minutes', 5);
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

    public static function touch(User $user): void
    {
        $user->forceFill(['last_seen_at' => now()])->save();
    }
}
