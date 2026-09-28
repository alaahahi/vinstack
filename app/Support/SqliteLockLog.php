<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * File-only SQLite busy/lock event logger.
 *
 * Writes JSONL under storage/logs/sqlite-locks/ and never touches the app DB.
 * Enable/disable is a JSON flag file (or config default when the flag is absent).
 */
final class SqliteLockLog
{
    private static ?bool $enabledCache = null;

    public static function forgetEnabledCache(): void
    {
        self::$enabledCache = null;
    }

    public static function isEnabled(): bool
    {
        if (self::$enabledCache !== null) {
            return self::$enabledCache;
        }

        $state = self::readState();

        if (is_array($state) && array_key_exists('enabled', $state)) {
            return self::$enabledCache = (bool) $state['enabled'];
        }

        return self::$enabledCache = (bool) config('sqlite_lock_log.enabled', true);
    }

    public static function setEnabled(bool $enabled): void
    {
        $path = self::statePath();
        $dir = dirname($path);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $payload = json_encode(
            [
                'enabled' => $enabled,
                'updated_at' => now()->toIso8601String(),
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload !== false) {
            File::put($path, $payload.PHP_EOL);
        }

        self::$enabledCache = $enabled;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function record(Throwable $e, string $outcome, array $extra = []): void
    {
        try {
            if (! self::isEnabled()) {
                return;
            }

            $record = array_merge([
                'ts' => now()->toIso8601String(),
                'outcome' => $outcome,
                'message' => self::truncate((string) $e->getMessage(), 500),
                'sql' => self::sqlFrom($e),
                'caller' => self::resolveCaller(),
                'route' => self::currentRoute(),
                'method' => self::currentMethod(),
                'uri' => self::currentUri(),
                'command' => self::currentCommand(),
                'pid' => getmypid() ?: null,
                'memory_mb' => round(memory_get_usage(true) / 1048576, 1),
            ], $extra);

            self::append($record);
        } catch (Throwable) {
            // Never interrupt the application.
        }
    }

    /**
     * @return array{
     *     enabled: bool,
     *     config_default: bool,
     *     state_path: string,
     *     log_path: string,
     *     today_total: int,
     *     by_outcome: array<string, int>,
     *     by_caller: list<array{name: string, count: int}>,
     *     by_route: list<array{name: string, count: int}>,
     *     by_command: list<array{name: string, count: int}>,
     *     by_hour: list<array{hour: string, count: int}>,
     *     recent: list<array<string, mixed>>,
     *     log_files: list<array{name: string, size_bytes: int, modified_at: ?string}>,
     *     log_size_bytes: int
     * }
     */
    public static function summarize(?string $date = null): array
    {
        $date = $date ?: now()->format('Y-m-d');
        $events = self::readEventsForDate($date);
        $maxRecent = max(1, (int) config('sqlite_lock_log.max_recent_events', 30));

        $byOutcome = [];
        $byCaller = [];
        $byRoute = [];
        $byCommand = [];
        $byHour = [];

        foreach ($events as $event) {
            $outcome = (string) ($event['outcome'] ?? 'unknown');
            $byOutcome[$outcome] = ($byOutcome[$outcome] ?? 0) + 1;

            $caller = (string) ($event['caller'] ?? 'unknown');
            $byCaller[$caller] = ($byCaller[$caller] ?? 0) + 1;

            $route = (string) ($event['route'] ?? $event['uri'] ?? '');
            if ($route !== '') {
                $byRoute[$route] = ($byRoute[$route] ?? 0) + 1;
            }

            $command = (string) ($event['command'] ?? '');
            if ($command !== '') {
                $byCommand[$command] = ($byCommand[$command] ?? 0) + 1;
            }

            $hour = self::hourBucket((string) ($event['ts'] ?? ''));
            if ($hour !== null) {
                $byHour[$hour] = ($byHour[$hour] ?? 0) + 1;
            }
        }

        ksort($byHour);

        return [
            'enabled' => self::isEnabled(),
            'config_default' => (bool) config('sqlite_lock_log.enabled', true),
            'state_path' => self::statePath(),
            'log_path' => self::logDir(),
            'date' => $date,
            'today_total' => count($events),
            'by_outcome' => $byOutcome,
            'by_caller' => self::topCounts($byCaller, 12),
            'by_route' => self::topCounts($byRoute, 12),
            'by_command' => self::topCounts($byCommand, 12),
            'by_hour' => array_map(
                static fn (string $hour, int $count): array => ['hour' => $hour, 'count' => $count],
                array_keys($byHour),
                array_values($byHour)
            ),
            'recent' => array_slice(array_reverse($events), 0, $maxRecent),
            'log_files' => self::listLogFiles(),
            'log_size_bytes' => self::totalLogSize(),
        ];
    }

    public static function clear(?string $date = null): int
    {
        $dir = self::logDir();

        if (! File::isDirectory($dir)) {
            return 0;
        }

        $deleted = 0;

        if ($date !== null) {
            $path = self::logFilePath($date);

            if (File::exists($path)) {
                File::delete($path);
                $deleted = 1;
            }

            return $deleted;
        }

        foreach (File::files($dir) as $file) {
            if (str_ends_with($file->getFilename(), '.jsonl')) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function pruneOldFiles(): int
    {
        $days = max(1, (int) config('sqlite_lock_log.retention_days', 14));
        $cutoff = now()->subDays($days)->startOfDay();
        $dir = self::logDir();
        $deleted = 0;

        if (! File::isDirectory($dir)) {
            return 0;
        }

        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();

            if (! preg_match('/^(\d{4}-\d{2}-\d{2})\.jsonl$/', $name, $m)) {
                continue;
            }

            try {
                $fileDate = Carbon::createFromFormat('Y-m-d', $m[1])->startOfDay();
            } catch (Throwable) {
                continue;
            }

            if ($fileDate->lt($cutoff)) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected static function append(array $record): void
    {
        $dir = self::logDir();

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        file_put_contents(
            self::logFilePath(now()->format('Y-m-d')),
            $line.PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function readEventsForDate(string $date): array
    {
        $path = self::logFilePath($date);

        if (! File::exists($path)) {
            return [];
        }

        $maxBytes = max(65_536, (int) config('sqlite_lock_log.max_read_bytes', 2_097_152));
        $size = filesize($path);

        if ($size === false || $size === 0) {
            return [];
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $readFrom = max(0, $size - $maxBytes);
        fseek($handle, $readFrom);
        $chunk = (string) fread($handle, $size - $readFrom);
        fclose($handle);

        if ($readFrom > 0) {
            $newline = strpos($chunk, "\n");

            if ($newline !== false) {
                $chunk = substr($chunk, $newline + 1);
            }
        }

        $events = [];

        foreach (preg_split("/\r\n|\n|\r/", $chunk) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $events[] = $decoded;
            }
        }

        return $events;
    }

    /**
     * @return list<array{name: string, size_bytes: int, modified_at: ?string}>
     */
    protected static function listLogFiles(): array
    {
        $dir = self::logDir();

        if (! File::isDirectory($dir)) {
            return [];
        }

        $files = [];

        foreach (File::files($dir) as $file) {
            if (! str_ends_with($file->getFilename(), '.jsonl')) {
                continue;
            }

            $mtime = $file->getMTime();
            $files[] = [
                'name' => $file->getFilename(),
                'size_bytes' => $file->getSize(),
                'modified_at' => $mtime ? date('c', $mtime) : null,
            ];
        }

        usort($files, static fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

        return $files;
    }

    protected static function totalLogSize(): int
    {
        $total = 0;

        foreach (self::listLogFiles() as $file) {
            $total += $file['size_bytes'];
        }

        return $total;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{name: string, count: int}>
     */
    protected static function topCounts(array $counts, int $limit): array
    {
        arsort($counts);

        $items = [];

        foreach (array_slice($counts, 0, $limit, true) as $name => $count) {
            $items[] = ['name' => (string) $name, 'count' => (int) $count];
        }

        return $items;
    }

    protected static function hourBucket(string $ts): ?string
    {
        if ($ts === '') {
            return null;
        }

        try {
            return Carbon::parse($ts)->format('H:00');
        } catch (Throwable) {
            return null;
        }
    }

    protected static function sqlFrom(Throwable $e): ?string
    {
        if ($e instanceof QueryException) {
            return self::truncate((string) $e->getSql(), 400);
        }

        $previous = $e->getPrevious();

        return $previous instanceof Throwable ? self::sqlFrom($previous) : null;
    }

    protected static function resolveCaller(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 16) as $frame) {
            $class = $frame['class'] ?? null;

            if ($class === null) {
                continue;
            }

            if (str_starts_with($class, 'App\\Support\\Sqlite')) {
                continue;
            }

            if (! str_starts_with($class, 'App\\')) {
                continue;
            }

            return $class.'::'.($frame['function'] ?? '?');
        }

        return null;
    }

    protected static function currentRoute(): ?string
    {
        try {
            if (! app()->bound('request')) {
                return null;
            }

            $route = request()->route();

            return $route ? (string) ($route->getName() ?: $route->uri()) : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function currentMethod(): ?string
    {
        try {
            if (! app()->runningInConsole() && app()->bound('request')) {
                return request()->method();
            }
        } catch (Throwable) {
            // ignore
        }

        return null;
    }

    protected static function currentUri(): ?string
    {
        try {
            if (! app()->runningInConsole() && app()->bound('request')) {
                return '/'.ltrim((string) request()->path(), '/');
            }
        } catch (Throwable) {
            // ignore
        }

        return null;
    }

    protected static function currentCommand(): ?string
    {
        try {
            if (app()->runningInConsole() && isset($_SERVER['argv'][1])) {
                return (string) $_SERVER['argv'][1];
            }
        } catch (Throwable) {
            // ignore
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function readState(): ?array
    {
        $path = self::statePath();

        if (! is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function statePath(): string
    {
        return (string) config('sqlite_lock_log.state_path', storage_path('app/sqlite-lock-log.json'));
    }

    protected static function logDir(): string
    {
        return (string) config('sqlite_lock_log.log_path', storage_path('logs/sqlite-locks'));
    }

    protected static function logFilePath(string $date): string
    {
        return rtrim(self::logDir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$date.'.jsonl';
    }

    protected static function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 1).'…';
    }
}
