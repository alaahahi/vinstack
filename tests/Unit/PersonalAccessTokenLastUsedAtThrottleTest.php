<?php

namespace Tests\Unit;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $firstUsed = now()->subSeconds(30);
        $token->forceFill(['last_used_at' => $firstUsed])->save();
        $token->refresh();

        $token->forceFill(['last_used_at' => now()])->save();
        $token->refresh();

        $this->assertTrue(
            $token->last_used_at->equalTo($firstUsed),
            'Expected last_used_at write to be skipped within throttle window'
        );

        $token->forceFill(['last_used_at' => now()->subSeconds(PersonalAccessToken::LAST_USED_AT_THROTTLE_SECONDS + 5)])->save();
        $token->refresh();

        $next = now();
        $token->forceFill(['last_used_at' => $next])->save();
        $token->refresh();

        $this->assertTrue(
            $token->last_used_at->greaterThanOrEqualTo($next->copy()->subSecond()),
            'Expected last_used_at to update after throttle window'
        );
    }
}
