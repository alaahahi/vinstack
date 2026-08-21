<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApibaraRequestLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_role',
        'user_name',
        'endpoint',
        'method',
        'query',
        'status',
        'cached',
        'billed',
        'elapsed_ms',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'query' => 'array',
            'cached' => 'boolean',
            'billed' => 'boolean',
            'status' => 'integer',
            'elapsed_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
