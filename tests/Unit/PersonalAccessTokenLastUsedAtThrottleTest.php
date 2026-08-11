<?php

namespace Tests\Unit;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PDOException;
use Tests\TestCase;

class PersonalAccessTokenLastUsedAtThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_uses_app_personal_access_token_model(): void
    {
        $this->assertSame(PersonalAccessToken::class, Sanctum::$personalAccessTokenModel);
    }

    public function test_last_used_at_updates_are_throttled(): void
    {
        config(['sanctum.skip_last_used_at_on_sqlite' => false]);

        $user = User::factory()->create();
        $token = $user->createToken('test')->accessToken;

        $this->assertInstanceOf(PersonalAccessToken::class, $token);

        $firstUsed = now()->subSeconds(30)->startOfSecond();
        $token->forceFill(['last_used_at' => $firstUsed])->save();
        $token->refresh();

        $before = $token->last_used_at?->toDateTimeString();

        $token->forceFill(['last_used_at' => now()])->save();
        $token->refresh();

        $this->assertSame(
            $before,
            $token->last_used_at?->toDateTimeString(),
            'Expected last_used_at write to be skipped within throttle window'
        );

        // Bypass model throttle to age the column, then assert a fresh touch is written.
        DB::table('personal_access_tokens')->where('id', $token->id)->update([
            'last_used_at' => now()->subSeconds(PersonalAccessToken::LAST_USED_AT_THROTTLE_SECONDS + 5),
        ]);
        $token->refresh();

        $next = now()->startOfSecond();
        $token->forceFill(['last_used_at' => $next])->save();
        $token->refresh();

        $this->assertSame(
            $next->toDateTimeString(),
            $token->last_used_at?->toDateTimeString(),
            'Expected last_used_at to update after throttle window'
        );
    }

    public function test_sqlite_skips_last_used_at_writes_when_enabled(): void
    {
        config(['sanctum.skip_last_used_at_on_sqlite' => true]);

        $user = User::factory()->create();
        /** @var PersonalAccessToken $token */
        $token = $user->createToken('test')->accessToken;

        DB::table('personal_access_tokens')->where('id', $token->id)->update([
            'last_used_at' => now()->subHours(5),
        ]);
        $token->refresh();

        $before = $token->last_used_at?->toDateTimeString();

        $token->forceFill(['last_used_at' => now()])->save();
        $token->refresh();

        $this->assertSame(
            $before,
            $token->last_used_at?->toDateTimeString(),
            'Expected last_used_at write to be skipped entirely on SQLite'
        );
    }

    public function test_last_used_at_save_swallows_query_exception(): void
    {
        config(['sanctum.skip_last_used_at_on_sqlite' => false]);
        config(['sanctum.last_used_at_throttle_seconds' => 0]);

        $token = new class extends PersonalAccessToken
        {
            protected function performUpdate(Builder $query)
            {
                throw new QueryException(
                    'sqlite',
                    'update "personal_access_tokens" set "last_used_at" = ? where "id" = ?',
                    [],
                    new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
                );
            }
        };

        $token->exists = true;
        $token->forceFill([
            'id' => 1,
            'tokenable_type' => User::class,
            'tokenable_id' => 1,
            'name' => 'test',
            'token' => 'hash',
            'abilities' => ['*'],
            'last_used_at' => now()->subHours(2),
        ]);
        $token->syncOriginal();
        $token->forceFill(['last_used_at' => now()]);

        $this->assertTrue($token->save());
    }
}
