<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuctionApiProvider extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'api_key',
        'monthly_quota',
        'sort_order',
        'is_enabled',
        'is_active',
        'quota_exhausted_at',
        'last_switched_at',
        'last_switch_reason',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'monthly_quota' => 'integer',
            'sort_order' => 'integer',
            'is_enabled' => 'boolean',
            'is_active' => 'boolean',
            'quota_exhausted_at' => 'datetime',
            'last_switched_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApibaraRequestLog::class, 'provider_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
