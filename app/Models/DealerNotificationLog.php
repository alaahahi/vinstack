<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerNotificationLog extends Model
{
    protected $fillable = [
        'dealer_id',
        'created_by',
        'phone',
        'message',
        'channel',
        'source',
        'event',
        'wa_queue_id',
        'wa_queue_status',
        'wa_queue_response',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'wa_queue_response' => 'array',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
