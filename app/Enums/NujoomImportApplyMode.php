<?php

namespace App\Enums;

enum NujoomImportApplyMode: string
{
    case All = 'all';
    case UpdatesOnly = 'updates_only';
    case AddOnly = 'add_only';
}
