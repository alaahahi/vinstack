<?php

namespace App\Support;

class SupportedLocale
{
    public const DEFAULT = 'ar';

    /** @var list<string> */
    public const CODES = ['ar', 'en', 'ckb'];

    public static function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, self::CODES, true) ? $locale : self::DEFAULT;
    }
}
