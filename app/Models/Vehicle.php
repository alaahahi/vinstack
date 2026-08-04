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
        'eta',
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

        // Chronological purchase_date DESC (nulls last), then created_at, then id.
        // Use DATE() so ISO timestamps and Y-m-d sort correctly — not as raw strings.
        if ($driver === 'sqlite') {
            $purchaseDate = "date(json_extract(raw_data, '$.purchase_date'))";

            return $query
                ->orderByRaw("CASE WHEN {$purchaseDate} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$purchaseDate} DESC")
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        }

        $purchaseDate = "DATE(JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.purchase_date')))";

        return $query
            ->orderByRaw("CASE WHEN {$purchaseDate} IS NULL THEN 1 ELSE 0 END ASC")
            ->orderByRaw("{$purchaseDate} DESC")
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
