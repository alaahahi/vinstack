<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ImageTransferJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test that ImageTransfer page properly displays partial success states,
 * distinguishing between transferred (successful) and failed images.
 */
class ImageTransferPartialStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_state_shows_both_succeeded_and_failed_counts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PARTIAL,
            'container_number' => 'MSCU1234567',
            'replace_existing' => false,
            'total_images' => 100,
            'transferred_count' => 99,
            'failed_count' => 1,
            'manifest' => [
                ...array_fill(0, 99, ['name' => 'success.jpg', 'status' => 'done']),
                ['name' => 'failed.jpg', 'status' => 'failed', 'error' => 'upload timeout'],
            ],
            'finished_at' => now(),
        ]);

        $response = $this->getJson("/api/admin/image-transfers/{$job->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_PARTIAL)
            ->assertJsonPath('data.transferred_count', 99)
            ->assertJsonPath('data.failed_count', 1)
            ->assertJsonPath('data.total_images', 100)
            ->assertJsonPath('data.progress_percent', 100);

        $this->assertCount(1, $response->json('data.failed_items'));
        $this->assertEquals('failed.jpg', $response->json('data.failed_items.0.name'));
    }

    public function test_completed_state_has_all_images_successful(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_COMPLETED,
            'container_number' => 'TCLU7654321',
            'replace_existing' => false,
            'total_images' => 78,
            'transferred_count' => 78,
            'failed_count' => 0,
            'manifest' => array_fill(0, 78, ['name' => 'success.jpg', 'status' => 'done']),
            'finished_at' => now(),
        ]);

        $response = $this->getJson("/api/admin/image-transfers/{$job->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_COMPLETED)
            ->assertJsonPath('data.transferred_count', 78)
            ->assertJsonPath('data.failed_count', 0)
            ->assertJsonPath('data.total_images', 78)
            ->assertJsonPath('data.progress_percent', 100);

        $this->assertEmpty($response->json('data.failed_items', []));
    }

    public function test_processing_state_shows_partial_progress(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'type' => ImageTransferJob::TYPE_VEHICLE_ZIP,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'vehicle_id' => null,
            'replace_existing' => false,
            'total_images' => 50,
            'transferred_count' => 30,
            'failed_count' => 2,
            'manifest' => [
                ...array_fill(0, 30, ['name' => 'done.jpg', 'status' => 'done']),
                ['name' => 'fail1.jpg', 'status' => 'failed', 'error' => 'network error'],
                ['name' => 'fail2.jpg', 'status' => 'failed', 'error' => 'file not found'],
                ...array_fill(0, 18, ['name' => 'pending.jpg', 'status' => 'pending']),
            ],
            'started_at' => now()->subMinutes(2),
        ]);

        $response = $this->getJson("/api/admin/image-transfers/{$job->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_PROCESSING)
            ->assertJsonPath('data.transferred_count', 30)
            ->assertJsonPath('data.failed_count', 2)
            ->assertJsonPath('data.total_images', 50)
            ->assertJsonPath('data.progress_percent', 64); // (30 + 2) / 50 = 64%

        $this->assertCount(2, $response->json('data.failed_items'));
    }

    public function test_failed_state_all_images_failed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_FAILED,
            'container_number' => 'MSCU9999999',
            'replace_existing' => false,
            'total_images' => 5,
            'transferred_count' => 0,
            'failed_count' => 5,
            'error_message' => 'All uploads failed due to network error',
            'manifest' => array_fill(0, 5, ['name' => 'failed.jpg', 'status' => 'failed', 'error' => 'network error']),
            'finished_at' => now(),
        ]);

        $response = $this->getJson("/api/admin/image-transfers/{$job->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_FAILED)
            ->assertJsonPath('data.transferred_count', 0)
            ->assertJsonPath('data.failed_count', 5)
            ->assertJsonPath('data.total_images', 5)
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.error_message', 'All uploads failed due to network error');

        $this->assertCount(5, $response->json('data.failed_items'));
    }
}
