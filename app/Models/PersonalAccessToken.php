<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use PDOException;
use Throwable;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Skip frequent last_used_at writes to reduce SQLite lock contention
     * under polling / concurrent API requests.
     */
    public const LAST_USED_AT_THROTTLE_SECONDS = 3600;

    /**
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->shouldSkipLastUsedAtWrite()) {
            // Discard in-memory dirty last_used_at / updated_at without writing.
            $this->syncOriginal();

            return true;
        }

        if ($this->isLastUsedAtOnlyDirtyWrite()) {
            return $this->saveLastUsedAtSafely($options);
        }

        return parent::save($options);
    }

    /**
     * Persist last_used_at without ever failing the HTTP request on lock errors.
     *
     * @param  array<string, mixed>  $options
     */
    protected function saveLastUsedAtSafely(array $options = []): bool
    {
        try {
            return parent::save($options);
        } catch (QueryException|PDOException $e) {
            // SQLite "database is locked" (and similar) must not crash auth.
            $this->syncOriginal();

            return true;
        }
    }

    protected function shouldSkipLastUsedAtWrite(): bool
    {
        if (! $this->isLastUsedAtOnlyDirtyWrite()) {
            return false;
        }

        // Zero writes on SQLite — production file DB locks otherwise.
        if ($this->shouldSkipLastUsedAtOnSqlite()) {
            return true;
        }

        $previous = $this->getOriginal('last_used_at');

        if ($previous === null) {
            return false;
        }

        try {
            $previousAt = $this->asDateTime($previous);
        } catch (Throwable) {
            return false;
        }

        $throttle = (int) config(
            'sanctum.last_used_at_throttle_seconds',
            self::LAST_USED_AT_THROTTLE_SECONDS
        );

        if ($throttle <= 0) {
            return false;
        }

        return $previousAt->greaterThan(now()->subSeconds($throttle));
    }

    protected function shouldSkipLastUsedAtOnSqlite(): bool
    {
        if (! filter_var(config('sanctum.skip_last_used_at_on_sqlite', true), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        try {
            return $this->getConnection()->getDriverName() === 'sqlite';
        } catch (Throwable) {
            return false;
        }
    }

    protected function isLastUsedAtOnlyDirtyWrite(): bool
    {
        if (! $this->isDirty('last_used_at')) {
            return false;
        }

        // Ignore automatic timestamp columns — Sanctum only force-fills last_used_at.
        $dirty = collect($this->getDirty())
            ->except([$this->getCreatedAtColumn(), $this->getUpdatedAtColumn()])
            ->all();

        return $dirty !== [] && array_keys($dirty) === ['last_used_at'];
    }
}
