<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Skip frequent last_used_at writes to reduce SQLite lock contention
     * under polling / concurrent API requests.
     */
    public const LAST_USED_AT_THROTTLE_SECONDS = 300;

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

        return parent::save($options);
    }

    protected function shouldSkipLastUsedAtWrite(): bool
    {
        if (! $this->isDirty('last_used_at')) {
            return false;
        }

        // Ignore automatic timestamp columns — Sanctum only force-fills last_used_at.
        $dirty = collect($this->getDirty())
            ->except([$this->getCreatedAtColumn(), $this->getUpdatedAtColumn()])
            ->all();

        if ($dirty !== [] && array_keys($dirty) !== ['last_used_at']) {
            return false;
        }

        if (! array_key_exists('last_used_at', $this->getDirty())) {
            return false;
        }

        $previous = $this->getOriginal('last_used_at');

        if ($previous === null) {
            return false;
        }

        try {
            $previousAt = $this->asDateTime($previous);
        } catch (\Throwable) {
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
}
