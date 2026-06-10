<?php

namespace App\Enums;

enum VehicleSource: string
{
    case Vinstack = 'vinstack';
    case Manual = 'manual';
    case NujoomAlJazeera = 'nujoom_al_jazeera';

    public function label(): string
    {
        return match ($this) {
            self::Vinstack => 'مستوردة',
            self::Manual => 'يدوية',
            self::NujoomAlJazeera => 'نجوم الجزيرة',
        };
    }
}
