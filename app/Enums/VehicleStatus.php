<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Reserved = 'reserved';
    case Imported = 'imported';
}
