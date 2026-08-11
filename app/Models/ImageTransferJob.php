<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageTransferJob extends Model
{
    public const TYPE_CONTAINER_ZIP = 'container_zip';

    public const TYPE_VEHICLE_ZIP = 'vehicle_zip';

    public const TYPE_VEHICLE_IMAGES = 'vehicle_images';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STALE_AFTER_MINUTES = 5;

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'container_number',
        'vehicle_id',
        'stage',
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
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
            self::STATUS_CANCELLED,
        ], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_QUEUED,
            self::STATUS_PROCESSING,
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

    public function isStale(int $minutes = self::STALE_AFTER_MINUTES): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $reference = $this->updated_at ?? $this->started_at ?? $this->created_at;

        if ($reference === null) {
            return false;
        }

        return $reference->lte(now()->subMinutes($minutes));
    }

    /**
     * Recalculate transferred/failed counters from the manifest.
     */
    public function recalculateCountersFromManifest(): void
    {
        $transferred = 0;
        $failed = 0;

        foreach ($this->manifest ?? [] as $item) {
            $status = $item['status'] ?? '';

            if ($status === 'done') {
                $transferred++;
            } elseif ($status === 'failed') {
                $failed++;
            }
        }

        $this->transferred_count = $transferred;
        $this->failed_count = $failed;
    }

    /**
     * @return list<array{name: string, error: string|null}>
     */
    public function failedManifestItems(): array
    {
        $items = [];

        foreach ($this->manifest ?? [] as $item) {
            if (($item['status'] ?? '') !== 'failed') {
                continue;
            }

            $items[] = [
                'name' => (string) ($item['name'] ?? 'image'),
                'error' => isset($item['error']) ? (string) $item['error'] : null,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(bool $includeFailedItems = false): array
    {
        $payload = [
            'id' => $this->uuid,
            'type' => $this->type,
            'status' => $this->status,
            'container_number' => $this->container_number,
            'vehicle_id' => $this->vehicle_id,
            'stage' => $this->stage,
            'vehicle_vin' => $this->vehicle?->vin,
            'total_images' => $this->total_images,
            'transferred_count' => $this->transferred_count,
            'failed_count' => $this->failed_count,
            'progress_percent' => $this->progressPercent(),
            'error_message' => $this->error_message,
            'is_stale' => $this->isStale(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($includeFailedItems || $this->failed_count > 0) {
            $payload['failed_items'] = $this->failedManifestItems();
        }

        return $payload;
    }
}
