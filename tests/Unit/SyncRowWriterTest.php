<?php

namespace Tests\Unit;

use App\Support\SyncRowWriter;
use Illuminate\Database\QueryException;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class SyncRowWriterTest extends TestCase
{
    public function test_it_reports_success_when_the_write_lands(): void
    {
        $ran = 0;

        $written = SyncRowWriter::attempt('test:sync', ['vehicle_id' => 1], function () use (&$ran) {
            $ran++;
        });

        $this->assertTrue($written);
        $this->assertSame(1, $ran);
    }

    public function test_it_retries_a_busy_write_and_succeeds(): void
    {
        $attempts = 0;

        $written = SyncRowWriter::attempt('test:sync', ['vehicle_id' => 1], function () use (&$attempts) {
            $attempts++;

            if ($attempts < 3) {
                throw $this->lockException();
            }
        });

        $this->assertTrue($written);
        $this->assertSame(3, $attempts);
    }

    public function test_it_gives_up_on_a_permanently_locked_row_without_throwing(): void
    {
        $attempts = 0;

        $written = SyncRowWriter::attempt('test:sync', ['vehicle_id' => 1], function () use (&$attempts) {
            $attempts++;

            throw $this->lockException();
        });

        // Caller keeps going with the rest of the batch instead of aborting.
        $this->assertFalse($written);
        $this->assertSame(SyncRowWriter::ATTEMPTS, $attempts);
    }

    public function test_it_isolates_a_non_lock_query_error_to_the_single_row(): void
    {
        $attempts = 0;

        $written = SyncRowWriter::attempt('test:sync', ['vehicle_id' => 1], function () use (&$attempts) {
            $attempts++;

            throw new QueryException(
                'sqlite',
                'insert into "vehicles" ...',
                [],
                new PDOException('SQLSTATE[23000]: Integrity constraint violation'),
            );
        });

        $this->assertFalse($written);
        // Not a lock, so it is not retried — just reported and skipped.
        $this->assertSame(1, $attempts);
    }

    public function test_it_lets_non_database_errors_bubble_up(): void
    {
        $this->expectException(RuntimeException::class);

        SyncRowWriter::attempt('test:sync', [], function () {
            throw new RuntimeException('a real bug, not a contended row');
        });
    }

    private function lockException(): QueryException
    {
        return new QueryException(
            'sqlite',
            'update "vehicles" set "raw_data" = ? where "id" = ?',
            [],
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked'),
        );
    }
}
