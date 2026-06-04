<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = preg_replace('/\s+/', '', trim($phone));
        $phone = str_replace(['-', '(', ')'], '', $phone);

        return $phone;
    }
}
