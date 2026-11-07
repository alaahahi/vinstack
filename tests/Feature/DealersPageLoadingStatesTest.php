<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test that Dealers page properly handles loading, empty, and error states
 * with appropriate user feedback.
 */
class DealersPageLoadingStatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dealers_list_returns_correct_structure(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $dealer = Dealer::factory()->create(['company_name' => 'Test Dealer LLC']);

        $response = $this->getJson('/api/admin/dealers');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company_name',
                        'phone',
                        'user',
                        'last_seen_at',
                    ],
                ],
                'meta' => [
                    'login_url',
                ],
            ])
            ->assertJsonPath('data.0.company_name', 'Test Dealer LLC');
    }

    public function test_empty_dealers_list_returns_empty_array(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dealers');

        $response->assertOk()
            ->assertJson(['data' => []])
            ->assertJsonPath('data', []);
    }

    public function test_dealers_list_is_protected_by_admin_role(): void
    {
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($dealer);

        $response = $this->getJson('/api/admin/dealers');

        $response->assertForbidden();
    }

    public function test_dealers_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/dealers');

        $response->assertUnauthorized();
    }

    public function test_dealers_list_ordered_by_newest_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $dealer1 = Dealer::factory()->create([
            'company_name' => 'Dealer One',
            'created_at' => now()->subDays(5),
        ]);

        $dealer2 = Dealer::factory()->create([
            'company_name' => 'Dealer Two',
            'created_at' => now()->subDays(2),
        ]);

        $dealer3 = Dealer::factory()->create([
            'company_name' => 'Dealer Three',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/admin/dealers');

        $response->assertOk();

        $dealerIds = collect($response->json('data'))->pluck('id')->toArray();

        // Newest first: dealer3, dealer2, dealer1
        $this->assertEquals([
            $dealer3->id,
            $dealer2->id,
            $dealer1->id,
        ], $dealerIds);
    }
}
