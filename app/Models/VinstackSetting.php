<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VinstackSetting extends Model
{
    protected $fillable = [
        'api_base_url',
        'api_token',
        'sync_enabled',
        'last_sync_at',
        'last_auto_sync_at',
        'support_phone',
        'vehicle_options',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'sync_enabled' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_auto_sync_at' => 'datetime',
            'vehicle_options' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'api_base_url' => config('services.vinstack.base_url'),
            'sync_enabled' => true,
        ]);
    }
}
