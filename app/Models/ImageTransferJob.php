<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageTransferJob extends Model
{
    public const TYPE_CONTAINER_ZIP = 'container_zip';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'container_number',
        'user_id',
        'replace_existing',
        'total_images',
        'transferred_count',
        'failed_count',
        'staging_dir',
        'manifest',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'replace_existing' => 'boolean',
            'manifest' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): int
    {
        if ($this->total_images <= 0) {
            return $this->isFinished() ? 100 : 0;
        }

        $done = $this->transferred_count + $this->failed_count;

        return min(100, (int) round(($done / $this->total_images) * 100));
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_PARTIAL,
            self::STATUS_FAILED,
        ], true);
    }

    public function hasPendingManifestItems(): bool
    {
        foreach ($this->manifest ?? [] as $item) {
            if (($item['status'] ?? '') === 'pending') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->uuid,
            'type' => $this->type,
            'status' => $this->status,
            'container_number' => $this->container_number,
            'total_images' => $this->total_images,
            'transferred_count' => $this->transferred_count,
            'failed_count' => $this->failed_count,
            'progress_percent' => $this->progressPercent(),
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
