<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class DatabaseInsightsService
{
    /**
     * @return array{
     *     driver: string,
     *     database_path: ?string,
     *     database_size_bytes: ?int,
     *     used_bytes: ?int,
     *     free_bytes: ?int,
     *     tables: list<array{
     *         name: string,
     *         rows: int,
     *         size_bytes: ?int,
     *         percent_of_db: ?float
     *     }>
     * }
     */
    public function summarize(): array
    {
        $driver = (string) DB::getDriverName();

        return match ($driver) {
            'sqlite' => $this->summarizeSqlite(),
            'mysql', 'mariadb' => $this->summarizeMysqlLike($driver),
            default => $this->summarizeGeneric($driver),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarizeSqlite(): array
    {
        $path = (string) config('database.connections.sqlite.database');
        $databaseSize = File::exists($path) ? (int) File::size($path) : null;

        $pageSize = (int) $this->scalar('PRAGMA page_size', 4096);
        $pageCount = (int) $this->scalar('PRAGMA page_count', 0);
        $freelistCount = (int) $this->scalar('PRAGMA freelist_count', 0);
        $usedBytes = $pageSize > 0 ? ($pageCount * $pageSize) - ($freelistCount * $pageSize) : null;
        $freeBytes = $pageSize > 0 ? $freelistCount * $pageSize : null;

        $sizeMap = [];

        try {
            $dbstat = DB::select('SELECT name, SUM(pgsize) AS size_bytes FROM dbstat GROUP BY name');

            foreach ($dbstat as $row) {
                $name = (string) ($row->name ?? '');

                if ($name === '' || str_starts_with($name, 'sqlite_')) {
                    continue;
                }

                $sizeMap[$name] = (int) ($row->size_bytes ?? 0);
            }
        } catch (Throwable) {
            // dbstat may be unavailable on some SQLite builds. Row counts still help.
        }

        $tables = DB::select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        $items = [];

        foreach ($tables as $table) {
            $name = (string) ($table->name ?? '');

            if ($name === '') {
                continue;
            }

            $rows = $this->countTableRows($name);
            $sizeBytes = $sizeMap[$name] ?? null;
            $items[] = [
                'name' => $name,
                'rows' => $rows,
                'size_bytes' => $sizeBytes,
                'percent_of_db' => $databaseSize && $sizeBytes !== null && $databaseSize > 0
                    ? round(($sizeBytes / $databaseSize) * 100, 2)
                    : null,
            ];
        }

        usort($items, function (array $a, array $b): int {
            return ($b['size_bytes'] ?? -1) <=> ($a['size_bytes'] ?? -1)
                ?: $b['rows'] <=> $a['rows'];
        });

        return [
            'driver' => 'sqlite',
            'database_path' => $path !== '' ? $path : null,
            'database_size_bytes' => $databaseSize,
            'used_bytes' => $usedBytes,
            'free_bytes' => $freeBytes,
            'tables' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarizeMysqlLike(string $driver): array
    {
        $database = (string) config("database.connections.{$driver}.database");
        $rows = DB::select(
            'SELECT TABLE_NAME AS name, TABLE_ROWS AS row_count, (DATA_LENGTH + INDEX_LENGTH) AS size_bytes
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC',
            [$database]
        );

        $items = [];
        $total = 0;

        foreach ($rows as $row) {
            $sizeBytes = (int) ($row->size_bytes ?? 0);
            $total += $sizeBytes;
            $items[] = [
                'name' => (string) ($row->name ?? ''),
                'rows' => (int) ($row->row_count ?? 0),
                'size_bytes' => $sizeBytes,
                'percent_of_db' => null,
            ];
        }

        foreach ($items as &$item) {
            $item['percent_of_db'] = $total > 0
                ? round(($item['size_bytes'] / $total) * 100, 2)
                : null;
        }

        return [
            'driver' => $driver,
            'database_path' => $database !== '' ? $database : null,
            'database_size_bytes' => $total,
            'used_bytes' => $total,
            'free_bytes' => null,
            'tables' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarizeGeneric(string $driver): array
    {
        return [
            'driver' => $driver,
            'database_path' => null,
            'database_size_bytes' => null,
            'used_bytes' => null,
            'free_bytes' => null,
            'tables' => [],
        ];
    }

    protected function countTableRows(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    protected function scalar(string $sql, int $default): int
    {
        try {
            $row = DB::selectOne($sql);

            if ($row === null) {
                return $default;
            }

            return (int) array_values((array) $row)[0];
        } catch (Throwable) {
            return $default;
        }
    }
}
