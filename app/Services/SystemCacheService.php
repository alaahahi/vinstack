<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemCacheService
{
    /** @var list<string> */
    private const DATABASE_CACHE_TABLES = ['cache', 'cache_locks'];

    public function __construct(
        protected VehiclePurchaseDateNormalizer $purchaseDates,
    ) {}

    /**
     * Clear application caches and invalidate versioned index caches.
     *
     * Also empties Laravel database cache tables when present (safe under
     * CACHE_STORE=database / SQLite). Session tables are left untouched unless
     * $clearSessions is true, so users are not forced to re-login by default.
     *
     * @return array{
     *     cleared: list<string>,
     *     database_tables_cleared: list<string>,
     *     sessions_cleared: bool,
     *     vehicle_index_version_bumped: bool,
     *     purchase_dates_normalized: int
     * }
     */
    public function clear(bool $clearSessions = false): array
    {
        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
        ];

        $cleared = [];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $cleared[] = $command;
            } catch (Throwable) {
                // Continue clearing remaining stores even if one command fails.
            }
        }

        $databaseTablesCleared = $this->clearDatabaseCacheTables();
        $sessionsCleared = $clearSessions ? $this->clearSessionsTable() : false;

        $normalized = 0;

        try {
            $normalized = $this->purchaseDates->normalizeAll()['updated'];
        } catch (Throwable) {
            // Cache clear should still succeed if date backfill fails.
        }

        AdminVehicleIndexCache::bumpVersion();

        return [
            'cleared' => $cleared,
            'database_tables_cleared' => $databaseTablesCleared,
            'sessions_cleared' => $sessionsCleared,
            'vehicle_index_version_bumped' => true,
            'purchase_dates_normalized' => $normalized,
        ];
    }

    /**
     * Empty cache / cache_locks rows when those tables exist.
     * Does not touch sessions.
     *
     * @return list<string>
     */
    private function clearDatabaseCacheTables(): array
    {
        $cleared = [];

        foreach (self::DATABASE_CACHE_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            try {
                DB::table($table)->delete();
                $cleared[] = $table;
            } catch (Throwable) {
                // Skip tables that cannot be cleared (permissions / locks).
            }
        }

        return $cleared;
    }

    /**
     * Empty the sessions table when present (forces users to re-login).
     */
    private function clearSessionsTable(): bool
    {
        if (! Schema::hasTable('sessions')) {
            return false;
        }

        try {
            DB::table('sessions')->delete();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
