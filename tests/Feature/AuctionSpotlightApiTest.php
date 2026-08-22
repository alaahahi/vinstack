<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuctionSpotlightItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuctionSpotlightApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_vehicle_is_shared_in_spotlight_for_everyone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);

        Sanctum::actingAs($dealer);

        $this->postJson('/api/auctions/spotlight', [
            'vin' => 'KNDNB5K33T6603272',
            'platform' => 'copart',
            'title' => '2026 KIA CARNIVAL',
            'make' => 'KIA',
            'model' => 'CARNIVAL',
            'year' => 2026,
            'thumb_urls' => ['https://example.com/a.jpg', 'https://example.com/b.jpg'],
            'current_bid_usd' => 1200,
        ])->assertCreated();

        Sanctum::actingAs($admin);

        $this->getJson('/api/auctions/spotlight')
            ->assertOk()
            ->assertJsonPath('meta.enabled', true)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.vin', 'KNDNB5K33T6603272')
            ->assertJsonPath('data.0.make', 'KIA');
    }

    public function test_uppercase_platform_and_nested_payload_are_accepted(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Dealer]));

        $this->postJson('/api/auctions/spotlight', [
            'vin' => 'UPPERCASEVIN001',
            'platform' => 'COPART',
            'year' => '2025',
            'title' => 'Test Truck',
            'make' => 'FORD',
            'pricing' => ['current_bid_usd' => 500],
            'media' => ['thumbs' => ['https://cdn.example.com/very/long/path/photo.jpg']],
            'ad' => ['formatted' => 'not-a-string'],
            'thumb_urls' => ['https://cdn.example.com/very/long/path/photo.jpg'],
            'current_bid_usd' => 500,
        ])->assertCreated()
            ->assertJsonPath('data.platform', 'copart')
            ->assertJsonPath('data.vin', 'UPPERCASEVIN001');

        $this->assertDatabaseHas('auction_spotlight_items', [
            'identifier' => 'UPPERCASEVIN001',
            'platform' => 'copart',
            'year' => 2025,
        ]);
    }

    public function test_admin_can_disable_spotlight(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);

        AuctionSpotlightItem::query()->create([
            'identifier' => 'VIN123',
            'vin' => 'VIN123',
            'make' => 'TOYOTA',
            'last_viewed_at' => now(),
            'view_count' => 1,
        ]);

        Sanctum::actingAs($admin);
        $this->putJson('/api/auctions/spotlight/settings', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        Sanctum::actingAs($dealer);
        $this->getJson('/api/auctions/spotlight')
            ->assertOk()
            ->assertJsonPath('meta.enabled', false)
            ->assertJsonCount(0, 'data');

        $this->postJson('/api/auctions/spotlight', [
            'vin' => 'NEWNOTSAVED',
            'make' => 'FORD',
        ])->assertOk();

        $this->assertDatabaseMissing('auction_spotlight_items', [
            'identifier' => 'NEWNOTSAVED',
        ]);
    }

    public function test_dealer_cannot_disable_spotlight(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Dealer]));

        $this->putJson('/api/auctions/spotlight/settings', ['enabled' => false])
            ->assertForbidden();
    }
}
