<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use PDOException;

/**
 * Per-row write guard for the hourly sync actions.
 *
 * A sync touches ~120 vehicles in one pass. Before this guard a single
 * contended row threw out of the action and the command reported
 * "failed with exit code [1]", abandoning every remaining vehicle. Rows are
 * independent, so a bad row is isolated, logged and counted instead.
 */
final class SyncRowWriter
{
    /**
     * SQLite already waits up to `busy_timeout` (60s) inside each attempt, so
     * these retries are an outer envelope for locks that outlive one wait —
     * not a busy-loop. Backoff is linear: 250ms, 500ms, 750ms, 1s.
     */
    public const ATTEMPTS = 5;

    public const BACKOFF_MS = 250;

    /**
     * @param  array<string, mixed>  $context  Identifying fields for the log line.
     * @param  callable(): mixed  $write
     * @return bool True when the row landed; false when it was given up on.
     */
    public static function attempt(string $sync, array $context, callable $write): bool
    {
        try {
            SqliteBusy::retry($write, self::ATTEMPTS, self::BACKOFF_MS);

            return true;
        } catch (QueryException|PDOException $e) {
            // Deliberately catches non-lock query errors too (a malformed row
            // must not cost us the rest of the batch); anything else bubbles.
            Log::error($sync.': row write failed, continuing', [
                ...$context,
                'sqlite_busy' => SqliteBusy::isBusy($e),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
