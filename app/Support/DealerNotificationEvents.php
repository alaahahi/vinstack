<?php

namespace App\Support;

final class DealerNotificationEvents
{
    public const VEHICLE_ASSIGNED = 'dealer.vehicle_assigned';

    public const VEHICLE_UPDATED = 'dealer.vehicle_updated';

    public const VEHICLE_IMAGES_ADDED = 'dealer.vehicle_images_added';

    public const CONTAINER_IMAGES_ADDED = 'dealer.container_images_added';

    public const LOGIN_CREDENTIALS = 'dealer.login_credentials';

    public const MANUAL_NOTIFICATION = 'dealer.manual_notification';

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            self::VEHICLE_ASSIGNED => true,
            self::VEHICLE_UPDATED => true,
            self::VEHICLE_IMAGES_ADDED => true,
            self::CONTAINER_IMAGES_ADDED => true,
            self::LOGIN_CREDENTIALS => true,
            self::MANUAL_NOTIFICATION => true,
        ];
    }

    /**
     * @return list<array{key: string, default: bool}>
     */
    public static function catalog(): array
    {
        return [
            ['key' => self::VEHICLE_ASSIGNED, 'default' => true],
            ['key' => self::VEHICLE_UPDATED, 'default' => true],
            ['key' => self::VEHICLE_IMAGES_ADDED, 'default' => true],
            ['key' => self::CONTAINER_IMAGES_ADDED, 'default' => true],
            ['key' => self::LOGIN_CREDENTIALS, 'default' => true],
            ['key' => self::MANUAL_NOTIFICATION, 'default' => true],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, bool>
     */
    public static function normalize(?array $stored): array
    {
        $merged = self::defaults();

        if (! is_array($stored)) {
            return $merged;
        }

        foreach (array_keys($merged) as $key) {
            if (array_key_exists($key, $stored)) {
                $merged[$key] = (bool) $stored[$key];
            }
        }

        return $merged;
    }
}
