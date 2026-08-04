<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminClearSystemCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_clear_system_cache_and_bump_vehicle_index_version(): void
    {
        Cache::put('admin_vehicle_index_cache_version', 3);
        Cache::put('probe_app_cache_key', 'stale');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/system/cache/clear');

        $response->assertOk()
            ->assertJsonPath('data.vehicle_index_version_bumped', true)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'cleared',
                    'vehicle_index_version_bumped',
                ],
            ]);

        $this->assertContains('cache:clear', $response->json('data.cleared'));
        $this->assertNull(Cache::get('probe_app_cache_key'));
        // cache:clear wipes the version key; bumpVersion then add(1)+increment => 2
        $this->assertSame(2, (int) Cache::get('admin_vehicle_index_cache_version', 1));
    }

    public function test_dealer_cannot_clear_system_cache(): void
    {
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($dealer);

        $this->postJson('/api/admin/system/cache/clear')
            ->assertForbidden();
    }
}
