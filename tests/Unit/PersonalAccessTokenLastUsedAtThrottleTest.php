<?php

namespace Tests\Unit;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
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
}
