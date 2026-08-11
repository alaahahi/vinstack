<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqliteConnectionPragmaTest extends TestCase
{
    public function test_default_sqlite_config_enables_wal_and_busy_timeout(): void
    {
        $this->assertSame(10000, (int) config('database.connections.sqlite.busy_timeout'));
        $this->assertSame('wal', strtolower((string) config('database.connections.sqlite.journal_mode')));
        $this->assertSame('NORMAL', strtoupper((string) config('database.connections.sqlite.synchronous')));
    }

    public function test_sqlite_file_connection_applies_wal_and_busy_timeout_pragmas(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vs_sqlite_').'.sqlite';
        file_put_contents($path, '');

        try {
            Config::set('database.connections.sqlite_pragma_test', [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 10000,
                'journal_mode' => 'wal',
                'synchronous' => 'NORMAL',
                'transaction_mode' => 'DEFERRED',
            ]);

            $pdo = DB::connection('sqlite_pragma_test')->getPdo();

            $busyTimeout = (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn();
            $journalMode = strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn());
            $synchronous = strtoupper((string) $pdo->query('PRAGMA synchronous')->fetchColumn());

            $this->assertSame(10000, $busyTimeout);
            $this->assertSame('wal', $journalMode);
            // SQLite returns 1 for NORMAL
            $this->assertContains($synchronous, ['1', 'NORMAL'], 'Expected NORMAL synchronous mode');
        } finally {
            DB::purge('sqlite_pragma_test');

            foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
