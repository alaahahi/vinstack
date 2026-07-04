<?php

namespace App\Support;

class SupportedLocale
{
    /** @deprecated Use NOTIFICATION_DEFAULT for dealers who never set a locale */
    public const DEFAULT = 'ar';

    public const NOTIFICATION_DEFAULT = 'ckb';

    /** @var list<string> */
    public const CODES = ['ar', 'en', 'ckb'];

    public static function forNotifications(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        if ($locale !== '' && in_array($locale, self::CODES, true)) {
            return $locale;
        }

        return self::NOTIFICATION_DEFAULT;
    }

    public static function isCustomized(?string $locale): bool
    {
        $locale = strtolower(trim((string) $locale));

        return $locale !== '' && in_array($locale, self::CODES, true);
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
