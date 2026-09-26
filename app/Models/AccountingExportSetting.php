<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingExportSetting extends Model
{
    protected $fillable = [
        'api_base_url',
        'api_token',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'api_base_url' => config('services.copart.base_url'),
            'enabled' => false,
        ]);
    }

    public function resolvedBaseUrl(): string
    {
        return rtrim((string) ($this->api_base_url ?: config('services.copart.base_url')), '/');
    }

    public function resolvedToken(): string
    {
        return trim((string) ($this->api_token ?: config('services.copart.token', '')));
    }
}
