<?php

namespace App\Support;

final class UploadLimits
{
    public const EXECUTION_SECONDS = 1800;

    public static function extendExecutionTime(): void
    {
        @set_time_limit(self::EXECUTION_SECONDS);
        @ini_set('max_execution_time', (string) self::EXECUTION_SECONDS);
    }
}
