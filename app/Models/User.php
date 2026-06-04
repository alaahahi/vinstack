<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\DealerPresence;
use App\Support\PhoneNormalizer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_codes_archive',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
            'recovery_codes_archived_at' => 'datetime',
        ];
    }

    public function touchLastSeen(): void
    {
        DealerPresence::touch($this);
    }

    public function dealer(): HasOne
    {
        return $this->hasOne(Dealer::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDealer(): bool
    {
        return $this->role === UserRole::Dealer;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = PhoneNormalizer::normalize($value);
    }

    public function scopeByPhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone', PhoneNormalizer::normalize($phone));
    }
}
