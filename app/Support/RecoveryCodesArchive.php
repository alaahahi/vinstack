<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class RecoveryCodesArchive
{
    /**
     * @param  list<string>  $codes
     */
    public static function archiveOnce(User $user, array $codes): void
    {
        $user->refresh();

        if ($user->recovery_codes_archive !== null) {
            return;
        }

        static::archive($user, $codes);
    }

    /**
     * @param  list<string>  $codes
     */
    public static function archive(User $user, array $codes): void
    {
        $user->forceFill([
            'recovery_codes_archive' => Crypt::encryptString(json_encode(array_values($codes))),
            'recovery_codes_archived_at' => now(),
        ])->save();
    }

    /**
     * @return list<string>|null
     */
    public static function decrypt(User $user): ?array
    {
        if (! $user->recovery_codes_archive) {
            return null;
        }

        $decoded = json_decode(Crypt::decryptString($user->recovery_codes_archive), true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_values(array_filter($decoded, fn ($code) => is_string($code) && $code !== ''));
    }

    public static function hasArchive(User $user): bool
    {
        return filled($user->recovery_codes_archive);
    }
}
