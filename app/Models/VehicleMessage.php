<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMessage extends Model
{
    protected $fillable = [
        'vehicle_id',
        'author_user_id',
        'author_role',
        'body',
        'attachment_url',
        'attachment_public_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'author_role' => UserRole::class,
            'read_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function hasContent(): bool
    {
        return filled($this->body) || filled($this->attachment_url);
    }
}
