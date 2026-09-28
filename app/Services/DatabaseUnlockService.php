<?php

namespace App\Services;

use App\Models\ImageTransferJob;
use App\Models\VinstackSetting;
use App\Support\SqliteBusy;
use App\Support\SqliteLockLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Best-effort SQLite lock recovery + diagnostics for the admin UI.
 *
 * SQLite cannot name the process holding the write lock from PHP. We infer
 * likely holders from active image transfers, WAL size, and the file lock log
 * (who hit SQLITE_BUSY — the victims, which usually points at the pressure source).
 */
class DatabaseUnlockService
{
    /**
     * @return array{
     *     driver: string,
     *     locked_now: bool,
     *     ping_ms: ?float,
     *     error: ?string,
     *     suspects: list<array{severity: string, kind: string, title: string, detail: string}>,
     *     active_transfers: list<array<string, mixed>>,
     *     lock_log: array<string, mixed>,
     *     wal: array{path: ?string, size_bytes: ?int},
     *     sync: array{last_sync_at: ?string, last_auto_sync_at: ?string, sync_enabled: ?bool}
     * }
     */
    public function diagnose(): array
    {
        $driver = (string) DB::getDriverName();
        $lockedNow = false;
        $pingMs = null;
        $error = null;

        if ($driver === 'sqlite') {
            try {
                // Short timeout so diagnose returns quickly when locked.
                DB::statement('PRAGMA busy_timeout = 2000');
                $started = microtime(true);
                DB::select('SELECT 1 AS ok');
                $pingMs = round((microtime(true) - $started) * 1000, 1);
            } catch (Throwable $e) {
                $lockedNow = true;
                $error = $e->getMessage();
            }
        }

        $activeTransfers = $this->activeTransfersSnapshot();
        $lockLog = $this->safeLockLogSummary();
        $wal = $this->walInfo();
        $sync = $this->syncInfo();
        $suspects = $this->buildSuspects($lockedNow, $activeTransfers, $lockLog, $wal, $sync);

        return [
            'driver' => $driver,
            'locked_now' => $lockedNow,
            'ping_ms' => $pingMs,
            'error' => $error,
            'suspects' => $suspects,
            'active_transfers' => $activeTransfers,
            'lock_log' => $lockLog,
            'wal' => $wal,
            'sync' => $sync,
        ];
    }

    /**
     * @return array{
     *     driver: string,
     *     ok: bool,
     *     ping_ms: ?float,
     *     checkpoint: ?array{busy: int, log: int, checkpointed: int},
     *     transfers_paused: int,
     *     steps: list<string>,
     *     error: ?string
     * }
     */
    public function attempt(bool $pauseTransfers = true): array
    {
        $driver = (string) DB::getDriverName();
        $steps = [];

        if ($driver !== 'sqlite') {
            return [
                'driver' => $driver,
                'ok' => true,
                'ping_ms' => null,
                'checkpoint' => null,
                'transfers_paused' => 0,
                'steps' => ['skipped_non_sqlite'],
                'error' => null,
            ];
        }

        $transfersPaused = 0;

        if ($pauseTransfers) {
            $transfersPaused = $this->pauseActiveTransfers($steps);
        }

        try {
            // :memory: SQLite is wiped by disconnect/reconnect — skip in tests.
            if (! $this->isMemorySqlite()) {
                DB::disconnect();
                $steps[] = 'disconnected';
                DB::reconnect();
                $steps[] = 'reconnected';
            }

            DB::statement('PRAGMA busy_timeout = 60000');
            $steps[] = 'busy_timeout_60s';

            $pingMs = $this->pingWithRetry($steps);

            $checkpoint = null;

            try {
                if (! $this->isMemorySqlite()) {
                    $checkpoint = $this->walCheckpoint($steps);
                } else {
                    $steps[] = 'checkpoint_skipped_memory';
                }
            } catch (Throwable $e) {
                if (! SqliteBusy::isBusy($e)) {
                    throw $e;
                }

                $steps[] = 'checkpoint_busy';
            }

            SqliteBusy::retry(function (): void {
                DB::select('SELECT 1 AS ok');
            }, attempts: 5, backoffMs: 100);

            $steps[] = 'writable_ok';

            return [
                'driver' => $driver,
                'ok' => true,
                'ping_ms' => $pingMs,
                'checkpoint' => $checkpoint,
                'transfers_paused' => $transfersPaused,
                'steps' => $steps,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $steps[] = 'failed';

            return [
                'driver' => $driver,
                'ok' => false,
                'ping_ms' => null,
                'checkpoint' => null,
                'transfers_paused' => $transfersPaused,
                'steps' => $steps,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  list<string>  $steps
     */
    protected function pingWithRetry(array &$steps): float
    {
        $started = microtime(true);

        SqliteBusy::retry(function (): void {
            DB::select('SELECT 1 AS ok');
        }, attempts: 8, backoffMs: 150);

        $ms = round((microtime(true) - $started) * 1000, 1);
        $steps[] = 'ping_ok';

        return $ms;
    }

    /**
     * @param  list<string>  $steps
     * @return array{busy: int, log: int, checkpointed: int}
     */
    protected function walCheckpoint(array &$steps): array
    {
        try {
            $row = DB::selectOne('PRAGMA wal_checkpoint(PASSIVE)');
            $steps[] = 'checkpoint_passive';
        } catch (Throwable) {
            $row = null;
        }

        try {
            $row = DB::selectOne('PRAGMA wal_checkpoint(TRUNCATE)') ?? $row;
            $steps[] = 'checkpoint_truncate';
        } catch (Throwable $e) {
            if (SqliteBusy::isBusy($e)) {
                $steps[] = 'checkpoint_truncate_busy';
            } else {
                throw $e;
            }
        }

        return [
            'busy' => (int) ($row->busy ?? 0),
            'log' => (int) ($row->log ?? 0),
            'checkpointed' => (int) ($row->checkpointed ?? 0),
        ];
    }

    /**
     * @param  list<string>  $steps
     */
    protected function pauseActiveTransfers(array &$steps): int
    {
        try {
            $jobs = ImageTransferJob::query()
                ->whereIn('status', [
                    ImageTransferJob::STATUS_QUEUED,
                    ImageTransferJob::STATUS_PROCESSING,
                ])
                ->get();

            $paused = 0;

            foreach ($jobs as $job) {
                $job->status = ImageTransferJob::STATUS_CANCELLED;
                $job->error_message = 'أُوقف مؤقتاً لفك قفل قاعدة البيانات — أعد التشغيل من نقل الصور.';
                $job->finished_at = now();

                try {
                    $job->save();
                    $paused++;
                } catch (Throwable $e) {
                    if (! SqliteBusy::isBusy($e)) {
                        throw $e;
                    }
                }
            }

            if ($paused > 0) {
                $steps[] = "transfers_paused:{$paused}";
            }

            return $paused;
        } catch (Throwable) {
            $steps[] = 'transfers_pause_failed';

            return 0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activeTransfersSnapshot(): array
    {
        try {
            return ImageTransferJob::query()
                ->whereIn('status', [
                    ImageTransferJob::STATUS_QUEUED,
                    ImageTransferJob::STATUS_PROCESSING,
                ])
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (ImageTransferJob $job) => [
                    'uuid' => $job->uuid,
                    'type' => $job->type,
                    'status' => $job->status,
                    'vehicle_id' => $job->vehicle_id,
                    'container_number' => $job->container_number,
                    'total_images' => $job->total_images,
                    'transferred_count' => $job->transferred_count,
                    'failed_count' => $job->failed_count,
                    'progress_percent' => $job->progressPercent(),
                    'is_stale' => $job->isStale(),
                    'updated_at' => $job->updated_at?->toIso8601String(),
                ])
                ->all();
        } catch (Throwable) {
            return $this->activeTransfersFromProgressFiles();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activeTransfersFromProgressFiles(): array
    {
        $dir = storage_path('app/image-transfers');

        if (! File::isDirectory($dir)) {
            return [];
        }

        $items = [];

        foreach (File::directories($dir) as $staging) {
            $progress = $staging.DIRECTORY_SEPARATOR.'progress.json';

            if (! is_file($progress)) {
                continue;
            }

            try {
                $decoded = json_decode((string) file_get_contents($progress), true);
            } catch (Throwable) {
                continue;
            }

            if (! is_array($decoded)) {
                continue;
            }

            $status = (string) ($decoded['status'] ?? '');

            if (! in_array($status, [
                ImageTransferJob::STATUS_QUEUED,
                ImageTransferJob::STATUS_PROCESSING,
            ], true)) {
                continue;
            }

            $items[] = [
                'uuid' => $decoded['uuid'] ?? basename($staging),
                'type' => 'unknown',
                'status' => $status,
                'vehicle_id' => null,
                'container_number' => null,
                'total_images' => is_array($decoded['manifest'] ?? null) ? count($decoded['manifest']) : null,
                'transferred_count' => (int) ($decoded['transferred_count'] ?? 0),
                'failed_count' => (int) ($decoded['failed_count'] ?? 0),
                'progress_percent' => null,
                'is_stale' => false,
                'updated_at' => $decoded['updated_at'] ?? null,
                'source' => 'progress_file',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    protected function safeLockLogSummary(): array
    {
        try {
            $summary = SqliteLockLog::summarize();

            return [
                'enabled' => $summary['enabled'],
                'today_total' => $summary['today_total'],
                'by_outcome' => $summary['by_outcome'],
                'by_caller' => array_slice($summary['by_caller'], 0, 5),
                'by_route' => array_slice($summary['by_route'], 0, 5),
                'by_command' => array_slice($summary['by_command'], 0, 5),
                'recent' => array_slice($summary['recent'], 0, 8),
            ];
        } catch (Throwable) {
            return [
                'enabled' => false,
                'today_total' => 0,
                'by_outcome' => [],
                'by_caller' => [],
                'by_route' => [],
                'by_command' => [],
                'recent' => [],
            ];
        }
    }

    /**
     * @return array{path: ?string, size_bytes: ?int}
     */
    protected function walInfo(): array
    {
        $dbPath = (string) config('database.connections.sqlite.database');
        $walPath = $dbPath !== '' ? $dbPath.'-wal' : null;

        if ($walPath === null || ! is_file($walPath)) {
            return ['path' => $walPath, 'size_bytes' => null];
        }

        return [
            'path' => $walPath,
            'size_bytes' => (int) filesize($walPath),
        ];
    }

    /**
     * @return array{last_sync_at: ?string, last_auto_sync_at: ?string, sync_enabled: ?bool}
     */
    protected function syncInfo(): array
    {
        try {
            $settings = VinstackSetting::current();

            return [
                'last_sync_at' => $settings->last_sync_at?->toIso8601String(),
                'last_auto_sync_at' => $settings->last_auto_sync_at?->toIso8601String(),
                'sync_enabled' => (bool) $settings->sync_enabled,
            ];
        } catch (Throwable) {
            return [
                'last_sync_at' => null,
                'last_auto_sync_at' => null,
                'sync_enabled' => null,
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $activeTransfers
     * @param  array<string, mixed>  $lockLog
     * @param  array{path: ?string, size_bytes: ?int}  $wal
     * @param  array{last_sync_at: ?string, last_auto_sync_at: ?string, sync_enabled: ?bool}  $sync
     * @return list<array{severity: string, kind: string, title: string, detail: string}>
     */
    protected function buildSuspects(
        bool $lockedNow,
        array $activeTransfers,
        array $lockLog,
        array $wal,
        array $sync,
    ): array {
        $suspects = [];

        if ($lockedNow) {
            $suspects[] = [
                'severity' => 'critical',
                'kind' => 'locked_now',
                'title' => 'قاعدة البيانات مقفلة الآن',
                'detail' => 'تعذّر SELECT خلال مهلة قصيرة — كاتب آخر ماسك القفل حالياً.',
            ];
        }

        if ($activeTransfers !== []) {
            $count = count($activeTransfers);
            $stale = count(array_filter($activeTransfers, static fn ($j) => ! empty($j['is_stale'])));
            $suspects[] = [
                'severity' => 'high',
                'kind' => 'image_transfers',
                'title' => "نقل صور نشط ({$count})",
                'detail' => $stale > 0
                    ? "منها {$stale} مهمة متوقفة/قديمة — الأقوى احتمالاً للمسبب."
                    : 'مهام queued/processing تكتب على SQLite أثناء الرفع.',
            ];
        }

        $todayTotal = (int) ($lockLog['today_total'] ?? 0);

        if ($todayTotal > 0) {
            $top = $lockLog['by_caller'][0]['name'] ?? null;
            $topCount = $lockLog['by_caller'][0]['count'] ?? 0;
            $short = is_string($top)
                ? (str_contains($top, '\\') ? (string) substr((string) strrchr($top, '\\'), 1) : $top)
                : 'unknown';
            $suspects[] = [
                'severity' => $todayTotal >= 20 ? 'high' : 'medium',
                'kind' => 'lock_log',
                'title' => "سجل القفل: {$todayTotal} حدث اليوم",
                'detail' => $top
                    ? "أكثر من اصطدم بالقفل: {$short} ({$topCount} مرة) — غالباً قرب مصدر الضغط."
                    : 'فعّل سجل القفل وراقب الأحداث التالية.',
            ];
        }

        $walBytes = $wal['size_bytes'] ?? null;

        if (is_int($walBytes) && $walBytes > 8 * 1024 * 1024) {
            $mb = round($walBytes / 1048576, 1);
            $suspects[] = [
                'severity' => 'medium',
                'kind' => 'wal',
                'title' => "ملف WAL كبير ({$mb} MB)",
                'detail' => 'كتابات كثيرة معلّقة — شغّل فك القفل (checkpoint) أو قلل الضغط.',
            ];
        }

        foreach (['by_command' => 'أمر Artisan', 'by_route' => 'مسار API'] as $key => $label) {
            $top = $lockLog[$key][0] ?? null;

            if (! is_array($top) || empty($top['name'])) {
                continue;
            }

            $suspects[] = [
                'severity' => 'medium',
                'kind' => $key,
                'title' => "{$label}: {$top['name']}",
                'detail' => "ظهر {$top['count']} مرة في أحداث القفل اليوم.",
            ];
        }

        $lastAuto = $sync['last_auto_sync_at'] ?? null;

        if (is_string($lastAuto) && $lastAuto !== '') {
            try {
                if (now()->diffInMinutes(Carbon::parse($lastAuto)) <= 10) {
                    $suspects[] = [
                        'severity' => 'low',
                        'kind' => 'sync',
                        'title' => 'مزامنة حديثة',
                        'detail' => "آخر مزامنة تلقائية: {$lastAuto}",
                    ];
                }
            } catch (Throwable) {
                // ignore
            }
        }

        if ($suspects === []) {
            $suspects[] = [
                'severity' => 'info',
                'kind' => 'none',
                'title' => 'لا مؤشرات ضغط واضحة',
                'detail' => 'القفل قد يكون لحظياً. فعّل سجل الأقفال وراقب عند تكرار المشكلة.',
            ];
        }

        return $suspects;
    }

    protected function isMemorySqlite(): bool
    {
        $database = (string) config('database.connections.sqlite.database');

        return $database === ':memory:' || str_contains($database, 'mode=memory');
    }
}
