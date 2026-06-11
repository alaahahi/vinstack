<?php

namespace App\Models;

use App\Support\VehicleImageStages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleUploadedImage extends Model
{
    protected $fillable = [
        'vehicle_id',
        'stage',
        'path',
        'cloudinary_url',
        'public_id',
        'original_name',
        'uploaded_by',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isCloudinary(): bool
    {
        return filled($this->cloudinary_url);
    }

    public function publicUrl(): string
    {
        if ($this->isCloudinary()) {
            return (string) $this->cloudinary_url;
        }

        if (! filled($this->path)) {
            return '';
        }

        return '/storage/'.str_replace('\\', '/', $this->path);
    }

    public static function isValidStage(string $stage): bool
    {
        return in_array($stage, VehicleImageStages::STAGES, true);
    }
}
