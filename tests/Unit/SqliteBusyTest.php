<?php

namespace Tests\Unit;

use App\Support\SqliteBusy;
use Illuminate\Database\QueryException;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class SqliteBusyTest extends TestCase
{
    public function test_is_busy_detects_database_locked_message(): void
    {
        $e = new QueryException(
            'sqlite',
            'insert into users',
            [],
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
        );

        $this->assertTrue(SqliteBusy::isBusy($e));
    }

    public function test_is_busy_detects_sqlite_busy_message(): void
    {
        $e = new PDOException('SQLSTATE[HY000]: General error: SQLITE_BUSY');

        $this->assertTrue(SqliteBusy::isBusy($e));
    }

    public function test_is_busy_returns_false_for_unrelated_errors(): void
    {
        $e = new QueryException(
            'sqlite',
            'insert into users',
            [],
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed')
        );

        $this->assertFalse(SqliteBusy::isBusy($e));
    }

    public function test_soft_swallows_busy_errors(): void
    {
        $called = false;

        SqliteBusy::soft(function () use (&$called): void {
            $called = true;
            throw new QueryException(
                'sqlite',
                'insert into apibara_request_logs',
                [],
                new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
            );
        });

        $this->assertTrue($called);
    }

    public function test_soft_rethrows_non_busy_errors(): void
    {
        $this->expectException(QueryException::class);

        SqliteBusy::soft(function (): void {
            throw new QueryException(
                'sqlite',
                'insert into apibara_request_logs',
                [],
                new PDOException('SQLSTATE[23000]: Integrity constraint violation')
            );
        });
    }

    public function test_retry_succeeds_after_transient_busy(): void
    {
        $attempts = 0;

        $result = SqliteBusy::retry(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 3) {
                throw new QueryException(
                    'sqlite',
                    'insert into users',
                    [],
                    new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
                );
            }

            return 'ok';
        }, attempts: 3, backoffMs: 1);

        $this->assertSame('ok', $result);
        $this->assertSame(3, $attempts);
    }

    public function test_retry_gives_up_after_max_attempts(): void
    {
        $this->expectException(QueryException::class);

        SqliteBusy::retry(function (): never {
            throw new QueryException(
                'sqlite',
                'insert into users',
                [],
                new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
            );
        }, attempts: 2, backoffMs: 1);
    }

    public function test_retry_does_not_retry_non_busy_errors(): void
    {
        $attempts = 0;

        try {
            SqliteBusy::retry(function () use (&$attempts): never {
                $attempts++;
                throw new RuntimeException('boom');
            }, attempts: 3, backoffMs: 1);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(1, $attempts);
        }
    }
}
