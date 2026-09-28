<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ImageTransferJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDatabaseUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_unlock_sqlite_and_pause_active_transfers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        ImageTransferJob::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => ImageTransferJob::TYPE_VEHICLE_IMAGES,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'user_id' => $admin->id,
            'total_images' => 3,
            'transferred_count' => 1,
            'failed_count' => 0,
            'manifest' => [
                ['name' => 'a.jpg', 'status' => 'done'],
                ['name' => 'b.jpg', 'status' => 'pending'],
            ],
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/system/database-unlock', [
            'pause_transfers' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.transfers_paused', 1);

        $this->assertSame(
            ImageTransferJob::STATUS_CANCELLED,
            ImageTransferJob::query()->first()?->status,
        );
    }

    public function test_admin_can_diagnose_lock_suspects(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        ImageTransferJob::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => ImageTransferJob::TYPE_VEHICLE_ZIP,
            'status' => ImageTransferJob::STATUS_QUEUED,
            'user_id' => $admin->id,
            'total_images' => 5,
            'transferred_count' => 0,
            'failed_count' => 0,
            'manifest' => [],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/system/database-lock-status')
            ->assertOk()
            ->assertJsonPath('data.driver', 'sqlite')
            ->assertJsonPath('data.locked_now', false)
            ->assertJsonStructure([
                'data' => [
                    'suspects',
                    'active_transfers',
                    'lock_log',
                    'wal',
                ],
            ]);

        $kinds = collect($response->json('data.suspects'))->pluck('kind')->all();
        $this->assertContains('image_transfers', $kinds);
    }

    public function test_dealer_cannot_unlock_database(): void
    {
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($dealer);

        $this->postJson('/api/admin/system/database-unlock')->assertForbidden();
    }
}
