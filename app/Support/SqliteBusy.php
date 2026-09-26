<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use PDOException;
use Throwable;

final class SqliteBusy
{
    public static function isBusy(Throwable $e): bool
    {
        $haystack = strtolower($e->getMessage());

        if (self::messageIsBusy($haystack)) {
            return true;
        }

        if ($e instanceof QueryException || $e instanceof PDOException) {
            $code = (string) $e->getCode();

            if ($code === '5' || $code === 'HY000') {
                return str_contains($haystack, 'locked') || str_contains($haystack, 'busy');
            }
        }

        $previous = $e->getPrevious();

        return $previous instanceof Throwable ? self::isBusy($previous) : false;
    }

    /**
     * Message-only variant, for callers that only have a failure string
     * (e.g. a queue failure payload) rather than the original Throwable.
     */
    public static function messageIsBusy(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'database is locked')
            || str_contains($haystack, 'sqlite_busy')
            || str_contains($haystack, 'sqlite busy')
            || str_contains($haystack, 'general error: 5');
    }

    /**
     * Run a non-critical write; swallow SQLite busy/lock errors.
     */
    public static function soft(callable $callback): void
    {
        try {
            $callback();
        } catch (QueryException|PDOException $e) {
            if (! self::isBusy($e)) {
                throw $e;
            }

            Log::debug('sqlite busy soft-fail', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retry a critical write a few times on SQLite busy/lock.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function retry(callable $callback, int $attempts = 3, int $backoffMs = 50): mixed
    {
        $attempts = max(1, $attempts);
        $last = null;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return $callback();
            } catch (QueryException|PDOException $e) {
                $last = $e;

                if (! self::isBusy($e) || $i === $attempts) {
                    throw $e;
                }

                usleep($backoffMs * 1000 * $i);
            }
        }

        throw $last ?? new \RuntimeException('SqliteBusy::retry failed without exception.');
    }
}
