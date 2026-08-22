<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuctionFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuctionFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_list_and_remove_favorite(): void
    {
        $user = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($user);

        $this->postJson('/api/auctions/favorites', [
            'slug_vin' => 'copart-4T1NZ1AK9LU034545',
            'vin' => '4T1NZ1AK9LU034545',
            'lot_number' => '60799856',
            'platform' => 'copart',
            'year' => 2020,
            'make' => 'TOYOTA',
            'model' => 'CAMRY',
            'current_bid_usd' => 3750,
            'thumb_url' => 'https://example.com/t.jpg',
        ])
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.identifier', 'copart-4T1NZ1AK9LU034545')
            ->assertJsonPath('data.make', 'TOYOTA');

        $this->assertDatabaseHas('auction_favorites', [
            'user_id' => $user->id,
            'identifier' => 'copart-4T1NZ1AK9LU034545',
            'vin' => '4T1NZ1AK9LU034545',
        ]);

        $this->getJson('/api/auctions/favorites')
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.lot_number', '60799856');

        $this->getJson('/api/auctions/favorites/ids')
            ->assertOk()
            ->assertJsonPath('data.0', 'copart-4T1NZ1AK9LU034545');

        $this->deleteJson('/api/auctions/favorites/copart-4T1NZ1AK9LU034545')
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('auction_favorites', [
            'user_id' => $user->id,
            'identifier' => 'copart-4T1NZ1AK9LU034545',
        ]);
    }

    public function test_favorites_are_scoped_per_user(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Admin]);
        $other = User::factory()->create(['role' => UserRole::Dealer]);

        AuctionFavorite::query()->create([
            'user_id' => $owner->id,
            'identifier' => 'vin-aaa',
            'platform' => 'iaai',
            'make' => 'HONDA',
        ]);

        Sanctum::actingAs($other);

        $this->getJson('/api/auctions/favorites')
            ->assertOk()
            ->assertJsonPath('meta.count', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_store_requires_identifier(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->postJson('/api/auctions/favorites', [
            'make' => 'TOYOTA',
        ])->assertStatus(422);
    }
}
