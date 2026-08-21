<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'identifier',
        'platform',
        'vin',
        'lot_number',
        'title',
        'year',
        'make',
        'model',
        'thumb_url',
        'current_bid_usd',
        'buy_now_usd',
        'location_display',
        'primary_damage',
        'auction_at',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_bid_usd' => 'float',
            'buy_now_usd' => 'float',
            'snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'platform' => $this->platform,
            'vin' => $this->vin,
            'lot_number' => $this->lot_number,
            'title' => $this->title,
            'year' => $this->year,
            'make' => $this->make,
            'model' => $this->model,
            'thumb_url' => $this->thumb_url,
            'current_bid_usd' => $this->current_bid_usd,
            'buy_now_usd' => $this->buy_now_usd,
            'location_display' => $this->location_display,
            'primary_damage' => $this->primary_damage,
            'auction_at' => $this->auction_at,
            'favorited_at' => $this->created_at?->toIso8601String(),
            'snapshot' => $this->snapshot,
        ];
    }
}
