<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TwoFactorToken
{
    public static function create(int $userId, string $type): string
    {
        $token = Str::random(64);

        Cache::put(self::cacheKey($type, $token), $userId, now()->addMinutes(5));

        return $token;
    }

    public static function userId(string $type, string $token): ?int
    {
        $userId = Cache::get(self::cacheKey($type, $token));

        return $userId ? (int) $userId : null;
    }

    public static function forget(string $type, string $token): void
    {
        Cache::forget(self::cacheKey($type, $token));
    }

    protected static function cacheKey(string $type, string $token): string
    {
        return "2fa:{$type}:{$token}";
    }
}
