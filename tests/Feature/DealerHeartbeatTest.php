<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DealerPresence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PDOException;
use Tests\TestCase;

class DealerHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealer_heartbeat_updates_last_seen_and_returns_ok(): void
    {
        $this->travelTo('2026-08-23 12:00:00');

        $dealer = User::factory()->create([
            'role' => UserRole::Dealer,
            'last_seen_at' => null,
        ]);

        Sanctum::actingAs($dealer);

        $this->postJson('/api/dealer/heartbeat')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $dealer->refresh();

        $this->assertNotNull($dealer->last_seen_at);
        $this->assertTrue($dealer->last_seen_at->equalTo(now()));
    }

    public function test_touch_is_throttled_within_window(): void
    {
        $this->travelTo('2026-08-23 12:00:00');
        config(['presence.touch_throttle_seconds' => 90]);

        $dealer = User::factory()->create([
            'role' => UserRole::Dealer,
            'last_seen_at' => now()->subSeconds(30),
        ]);

        $before = $dealer->last_seen_at->toDateTimeString();

        DealerPresence::touch($dealer);
        $dealer->refresh();

        $this->assertSame(
            $before,
            $dealer->last_seen_at->toDateTimeString(),
            'Expected last_seen_at write to be skipped within throttle window'
        );

        $this->travel(100)->seconds();
        DealerPresence::touch($dealer);
        $dealer->refresh();

        $this->assertTrue($dealer->last_seen_at->equalTo(now()));
    }

    public function test_touch_swallows_database_lock_exceptions(): void
    {
        config(['presence.touch_throttle_seconds' => 0]);

        $user = new class extends User
        {
            public function newQuery()
            {
                $builder = \Mockery::mock(Builder::class);
                $builder->shouldReceive('whereKey')->once()->andReturnSelf();
                $builder->shouldReceive('toBase')->once()->andReturnSelf();
                $builder->shouldReceive('update')->once()->andThrow(new QueryException(
                    'sqlite',
                    'update "users" set "last_seen_at" = ? where "id" = ?',
                    [],
                    new PDOException('SQLSTATE[HY000]: General error: 5 database is locked')
                ));

                return $builder;
            }
        };

        $user->forceFill([
            'id' => 1,
            'name' => 'Locked Dealer',
            'email' => 'locked@example.com',
            'role' => UserRole::Dealer,
            'last_seen_at' => null,
        ]);
        $user->exists = true;
        $user->syncOriginal();

        DealerPresence::touch($user);

        $this->assertTrue(true, 'Expected DealerPresence::touch to swallow lock errors');
    }

    public function test_heartbeat_returns_ok_even_when_presence_write_is_skipped(): void
    {
        $this->travelTo('2026-08-23 12:00:00');
        config(['presence.touch_throttle_seconds' => 90]);

        $dealer = User::factory()->create([
            'role' => UserRole::Dealer,
            'last_seen_at' => now()->subSeconds(10),
        ]);

        Sanctum::actingAs($dealer);

        $this->postJson('/api/dealer/heartbeat')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
