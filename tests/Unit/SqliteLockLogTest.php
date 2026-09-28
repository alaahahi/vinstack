<?php

namespace Tests\Unit;

use App\Support\SqliteBusy;
use App\Support\SqliteLockLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use PDOException;
use Tests\TestCase;

class SqliteLockLogTest extends TestCase
{
    private string $logDir;

    private string $statePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = storage_path('framework/testing/sqlite-locks-'.uniqid());
        $this->statePath = storage_path('framework/testing/sqlite-lock-state-'.uniqid().'.json');

        config([
            'sqlite_lock_log.enabled' => true,
            'sqlite_lock_log.log_path' => $this->logDir,
            'sqlite_lock_log.state_path' => $this->statePath,
            'sqlite_lock_log.max_recent_events' => 10,
        ]);

        SqliteLockLog::forgetEnabledCache();
        File::ensureDirectoryExists($this->logDir);
    }

    protected function tearDown(): void
    {
        SqliteLockLog::forgetEnabledCache();

        if (File::isDirectory($this->logDir)) {
            File::deleteDirectory($this->logDir);
        }

        if (File::exists($this->statePath)) {
            File::delete($this->statePath);
        }

        parent::tearDown();
    }

    public function test_records_busy_events_to_jsonl_file(): void
    {
        $e = new QueryException(
            'sqlite',
            'insert into users (id) values (1)',
            [],
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
        );

        SqliteBusy::soft(function () use ($e): void {
            throw $e;
        });

        $summary = SqliteLockLog::summarize();

        $this->assertSame(1, $summary['today_total']);
        $this->assertSame(1, $summary['by_outcome']['soft_fail'] ?? 0);
        $this->assertNotEmpty($summary['recent']);
        $this->assertStringContainsString('insert into users', (string) ($summary['recent'][0]['sql'] ?? ''));
    }

    public function test_disabled_flag_skips_writing(): void
    {
        SqliteLockLog::setEnabled(false);

        $e = new QueryException(
            'sqlite',
            'update tokens set last_used_at = ?',
            [],
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
        );

        SqliteBusy::soft(function () use ($e): void {
            throw $e;
        });

        $this->assertSame(0, SqliteLockLog::summarize()['today_total']);
        $this->assertFalse(SqliteLockLog::isEnabled());
    }

    public function test_retry_logs_retry_then_exhausted(): void
    {
        $attempts = 0;

        try {
            SqliteBusy::retry(function () use (&$attempts): never {
                $attempts++;
                throw new QueryException(
                    'sqlite',
                    'update vehicles set status = ?',
                    [],
                    new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
                );
            }, attempts: 2, backoffMs: 1);
            $this->fail('Expected QueryException');
        } catch (QueryException) {
            // expected
        }

        $summary = SqliteLockLog::summarize();

        $this->assertSame(2, $attempts);
        $this->assertSame(1, $summary['by_outcome']['retry'] ?? 0);
        $this->assertSame(1, $summary['by_outcome']['exhausted'] ?? 0);
    }

    public function test_clear_removes_log_files(): void
    {
        SqliteLockLog::record(
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked'),
            'soft_fail'
        );

        $this->assertGreaterThan(0, SqliteLockLog::summarize()['today_total']);
        $this->assertSame(1, SqliteLockLog::clear());
        $this->assertSame(0, SqliteLockLog::summarize()['today_total']);
    }

    public function test_summarize_reads_only_files_not_database(): void
    {
        SqliteLockLog::record(
            new PDOException('database is locked'),
            'soft_fail',
            ['caller' => 'App\\Services\\Demo::run']
        );

        $summary = SqliteLockLog::summarize();

        $this->assertTrue($summary['enabled']);
        $this->assertSame('App\\Services\\Demo::run', $summary['by_caller'][0]['name'] ?? null);
        $this->assertStringContainsString('sqlite-locks', $summary['log_path']);
    }
}
