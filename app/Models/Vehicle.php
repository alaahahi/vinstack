<?php

namespace App\Models;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source',
        'vinstack_id',
        'vin',
        'make',
        'model',
        'year',
        'price',
        'status',
        'images',
        'raw_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'source' => VehicleSource::class,
            'status' => VehicleStatus::class,
            'images' => 'array',
            'raw_data' => 'array',
            'price' => 'decimal:2',
            'year' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(VehicleAssignment::class)->where('is_active', true);
    }

    public function uploadedImages(): HasMany
    {
        return $this->hasMany(VehicleUploadedImage::class);
    }

    public function scopeNewestFirst(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query
                ->orderByRaw(
                    "COALESCE(NULLIF(json_extract(raw_data, '$.purchase_date'), ''), NULLIF(json_extract(raw_data, '$.created_at'), ''), datetime(created_at)) DESC"
                )
                ->orderByDesc('id');
        }

        return $query
            ->orderByRaw(
                "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.purchase_date')), ''), NULLIF(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.created_at')), ''), created_at) DESC"
            )
            ->orderByDesc('id');
    }
}
