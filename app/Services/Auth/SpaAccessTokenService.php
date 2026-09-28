<?php

namespace App\Services\Auth;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/**
 * Issues signed SPA bearer tokens without writing personal_access_tokens.
 * Avoids SQLite "database is locked" on login under concurrent traffic.
 */
class SpaAccessTokenService
{
    public const TOKEN_NAME = 'spa';

    public const TOKEN_PREFIX = 'st1.';

    public function issue(User $user): string
    {
        $expirationMinutes = (int) config('sanctum.expiration', 525600);
        $expiresAt = $expirationMinutes > 0
            ? now()->addMinutes($expirationMinutes)
            : now()->addYear();

        return $this->encode([
            'uid' => (int) $user->id,
            'typ' => 'access',
            'name' => self::TOKEN_NAME,
            'abilities' => ['*'],
            'exp' => $expiresAt->getTimestamp(),
            'jti' => (string) Str::uuid(),
        ]);
    }

    public function isStatelessToken(?string $plain): bool
    {
        return is_string($plain) && str_starts_with($plain, self::TOKEN_PREFIX);
    }

    public function resolveAccessTokenModel(string $plain): ?PersonalAccessToken
    {
        $payload = $this->decode($plain);

        if (! $payload
            || ($payload['typ'] ?? null) !== 'access'
            || ($payload['name'] ?? null) !== self::TOKEN_NAME
            || (int) ($payload['exp'] ?? 0) < time()
        ) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find((int) ($payload['uid'] ?? 0));

        if (! $user) {
            return null;
        }

        $token = new PersonalAccessToken([
            'name' => self::TOKEN_NAME,
            'token' => hash('sha256', $plain),
            'abilities' => $payload['abilities'] ?? ['*'],
            'expires_at' => now()->setTimestamp((int) $payload['exp']),
        ]);

        $token->forceFill([
            'id' => 0,
            'tokenable_type' => $user->getMorphClass(),
            'tokenable_id' => $user->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token->exists = true;
        $token->markAsStateless();
        $token->setRelation('tokenable', $user);

        return $token;
    }

    /**
     * @param  array{uid:int,typ:string,name:string,abilities:array<int,string>,exp:int,jti:string}  $payload
     */
    private function encode(array $payload): string
    {
        return self::TOKEN_PREFIX.Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{uid?:int,typ?:string,name?:string,abilities?:array<int,string>,exp?:int,jti?:string}|null
     */
    private function decode(string $plain): ?array
    {
        if (! $this->isStatelessToken($plain)) {
            return null;
        }

        try {
            $json = Crypt::decryptString(substr($plain, strlen(self::TOKEN_PREFIX)));
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }
}
