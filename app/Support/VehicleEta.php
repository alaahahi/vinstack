<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;

class VehicleEta
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface || $value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        try {
            return now()->parse($string)->format('Y-m-d');
        } catch (\Throwable) {
            return $string;
        }
    }
}
