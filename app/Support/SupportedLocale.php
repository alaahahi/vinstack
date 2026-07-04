<?php

namespace App\Support;

class SupportedLocale
{
    public const NOTIFICATION_DEFAULT = 'ckb';

    /** @var list<string> */
    public const CODES = ['ar', 'en', 'ckb'];

    public static function forNotifications(?string $locale, bool $customized = false): string
    {
        if (! $customized) {
            return self::NOTIFICATION_DEFAULT;
        }

        $locale = strtolower(trim((string) $locale));

        if ($locale !== '' && in_array($locale, self::CODES, true)) {
            return $locale;
        }

        return self::NOTIFICATION_DEFAULT;
    }

    public static function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, self::CODES, true) ? $locale : self::NOTIFICATION_DEFAULT;
    }

    public static function nativeLabel(string $locale): string
    {
        return match ($locale) {
            'en' => 'English',
            'ckb' => 'کوردی',
            default => 'العربية',
        };
    }
}
