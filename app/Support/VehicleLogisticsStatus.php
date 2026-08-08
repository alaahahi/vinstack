<?php

namespace App\Support;

final class VehicleLogisticsStatus
{
    public const NEW_PURCHASE = 'new_purchase';

    public const SENT = 'sent';

    public const AT_TERMINAL = 'at_terminal';

    public const LEFT_TERMINAL = 'left_terminal';

    public const LOADED = 'loaded';

    public const OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::NEW_PURCHASE,
            self::SENT,
            self::AT_TERMINAL,
            self::LEFT_TERMINAL,
            self::LOADED,
            self::OTHER,
        ];
    }

    public static function bucket(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        if ($value === '') {
            return self::OTHER;
        }

        if (str_contains($value, 'left terminal')
            || str_contains($value, 'departed')
            || str_contains($value, 'غادر')) {
            return self::LEFT_TERMINAL;
        }

        if (str_contains($value, 'loaded') || str_contains($value, 'تحميل')) {
            return self::LOADED;
        }

        if (str_contains($value, 'terminal')
            || str_contains($value, 'at port')
            || str_contains($value, 'المحطة')) {
            return self::AT_TERMINAL;
        }

        if (str_contains($value, 'ship')
            || str_contains($value, 'transit')
            || str_contains($value, 'sent')
            || str_contains($value, 'dispatch')
            || str_contains($value, 'إرسال')) {
            return self::SENT;
        }

        if (str_contains($value, 'purchase')
            || str_contains($value, 'bought')
            || str_contains($value, 'شراء')
            || preg_match('/\bnew\b/', $value)) {
            return self::NEW_PURCHASE;
        }

        return self::OTHER;
    }
}
