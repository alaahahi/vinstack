<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionSpotlightItem extends Model
{
    protected $fillable = [
        'identifier',
        'platform',
        'vin',
        'lot_number',
        'title',
        'year',
        'make',
        'model',
        'thumb_urls',
        'current_bid_usd',
        'location_display',
        'primary_damage',
        'last_viewed_by',
        'last_viewed_at',
        'view_count',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_bid_usd' => 'float',
            'thumb_urls' => 'array',
            'view_count' => 'integer',
            'last_viewed_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_viewed_by');
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
            'thumb_urls' => array_values(array_filter($this->thumb_urls ?? [])),
            'current_bid_usd' => $this->current_bid_usd,
            'location_display' => $this->location_display,
            'primary_damage' => $this->primary_damage,
            'last_viewed_at' => $this->last_viewed_at?->toIso8601String(),
            'view_count' => $this->view_count,
            'media' => [
                'thumbs' => array_values(array_filter($this->thumb_urls ?? [])),
            ],
            'pricing' => [
                'current_bid_usd' => $this->current_bid_usd,
            ],
            'location' => [
                'display' => $this->location_display,
            ],
            'condition' => [
                'primary_damage' => $this->primary_damage,
            ],
        ];
    }
}
