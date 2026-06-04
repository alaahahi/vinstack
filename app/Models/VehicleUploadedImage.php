<?php

namespace App\Models;

use App\Support\VehicleImageStages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VehicleUploadedImage extends Model
{
    protected $fillable = [
        'vehicle_id',
        'stage',
        'path',
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

    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public static function isValidStage(string $stage): bool
    {
        return in_array($stage, VehicleImageStages::STAGES, true);
    }
}
