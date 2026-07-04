<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DealerPresence;
use App\Support\TwoFactorToken;
use Illuminate\Validation\ValidationException;

trait FormatsAuthUser
{
    protected function userPayload(User $user): array
    {
        $user->loadMissing('dealer');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'locale' => $user->locale ?? 'ar',
            'two_factor_enabled' => $user->isDealer() && $user->hasEnabledTwoFactorAuthentication(),
            'dealer' => $user->dealer ? [
                'id' => $user->dealer->id,
                'company_name' => $user->dealer->company_name,
            ] : null,
        ];
    }

    protected function issueToken(User $user): array
    {
        if ($user->isDealer()) {
            DealerPresence::touch($user);
        }

        $expirationMinutes = config('sanctum.expiration');
        $expiresAt = $expirationMinutes
            ? now()->addMinutes((int) $expirationMinutes)
            : null;

        $token = $user->createToken('spa', ['*'], $expiresAt)->plainTextToken;

        return [
            'token' => $token,
            'user' => $this->userPayload($user->fresh()),
        ];
    }

    protected function resolveDealerFromToken(string $type, string $token, string $errorField = 'setup_token'): User
    {
        $userId = TwoFactorToken::userId($type, $token);

        if (! $userId) {
            throw ValidationException::withMessages([
                $errorField => ['انتهت صلاحية الجلسة. سجّل الدخول من جديد.'],
            ]);
        }

        $user = User::query()->find($userId);

        if (! $user || $user->role !== UserRole::Dealer) {
            throw ValidationException::withMessages([
                $errorField => ['غير مصرح.'],
            ]);
        }

        return $user;
    }
}
