<?php

namespace App\Models;

use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'phone',
    ];

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = PhoneNormalizer::normalize($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('is_active', true);
    }
}
