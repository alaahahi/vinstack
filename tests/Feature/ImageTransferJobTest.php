<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\ProcessImageTransferBatch;
use App\Models\ImageTransferJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImageTransferJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_transfer_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'container_number' => 'MSCU1234567',
            'replace_existing' => true,
            'total_images' => 5,
            'transferred_count' => 2,
            'failed_count' => 0,
            'manifest' => [],
        ]);

        $this->getJson('/api/admin/image-transfers/'.$job->uuid)
            ->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_PROCESSING)
            ->assertJsonPath('data.progress_percent', 40);
    }

    public function test_transfer_list_is_available_for_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        ImageTransferJob::query()->create([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_COMPLETED,
            'container_number' => 'TCLU9999999',
            'replace_existing' => false,
            'total_images' => 1,
            'transferred_count' => 1,
            'failed_count' => 0,
            'manifest' => [],
        ]);

        $this->getJson('/api/admin/image-transfers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('meta.active_count', 0)
            ->assertJsonPath('meta.stale_count', 0);
    }

    public function test_admin_can_paginate_transfer_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        for ($i = 1; $i <= 12; $i++) {
            ImageTransferJob::query()->create([
                'uuid' => sprintf('33333333-3333-3333-3333-%012d', $i),
                'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
                'status' => ImageTransferJob::STATUS_COMPLETED,
                'container_number' => sprintf('MSCU%07d', $i),
                'replace_existing' => false,
                'total_images' => 1,
                'transferred_count' => 1,
                'failed_count' => 0,
                'manifest' => [],
            ]);
        }

        $this->getJson('/api/admin/image-transfers?page=1&per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.has_more', true);

        $this->getJson('/api/admin/image-transfers?page=2&per_page=10')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.has_more', false);
    }

    public function test_transfer_show_includes_failed_items_and_error(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '44444444-4444-4444-4444-444444444444',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PARTIAL,
            'container_number' => 'MSCU4444444',
            'replace_existing' => false,
            'total_images' => 2,
            'transferred_count' => 1,
            'failed_count' => 1,
            'error_message' => 'بعض الصور فشلت',
            'manifest' => [
                ['name' => 'ok.jpg', 'status' => 'done', 'error' => null],
                ['name' => 'bad.jpg', 'status' => 'failed', 'error' => 'upload timeout'],
            ],
            'finished_at' => now(),
        ]);

        $this->getJson('/api/admin/image-transfers/'.$job->uuid)
            ->assertOk()
            ->assertJsonPath('data.error_message', 'بعض الصور فشلت')
            ->assertJsonPath('data.failed_items.0.name', 'bad.jpg')
            ->assertJsonPath('data.failed_items.0.error', 'upload timeout');
    }

    public function test_admin_can_cancel_active_transfer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '55555555-5555-5555-5555-555555555555',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'container_number' => 'MSCU5555555',
            'replace_existing' => false,
            'total_images' => 3,
            'transferred_count' => 1,
            'failed_count' => 0,
            'manifest' => [
                ['name' => 'a.jpg', 'status' => 'done'],
                ['name' => 'b.jpg', 'status' => 'pending'],
                ['name' => 'c.jpg', 'status' => 'pending'],
            ],
        ]);

        $this->postJson('/api/admin/image-transfers/'.$job->uuid.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', ImageTransferJob::STATUS_CANCELLED);

        $this->assertDatabaseHas('image_transfer_jobs', [
            'uuid' => $job->uuid,
            'status' => ImageTransferJob::STATUS_CANCELLED,
        ]);
    }

    public function test_admin_can_process_now_active_transfer(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '66666666-6666-6666-6666-666666666666',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_QUEUED,
            'container_number' => 'MSCU6666666',
            'replace_existing' => false,
            'total_images' => 1,
            'transferred_count' => 0,
            'failed_count' => 0,
            'manifest' => [
                [
                    'name' => 'missing.jpg',
                    'path' => storage_path('app/image-transfers/missing.jpg'),
                    'status' => 'pending',
                ],
            ],
            'staging_dir' => storage_path('app/image-transfers/test-staging'),
        ]);

        $this->postJson('/api/admin/image-transfers/'.$job->uuid.'/process-now')
            ->assertOk()
            ->assertJsonPath('data.id', $job->uuid);

        $job->refresh();

        $this->assertTrue(in_array($job->status, [
            ImageTransferJob::STATUS_PROCESSING,
            ImageTransferJob::STATUS_PARTIAL,
            ImageTransferJob::STATUS_FAILED,
            ImageTransferJob::STATUS_COMPLETED,
        ], true));
    }

    public function test_admin_can_retry_failed_items_and_recalculate_counters(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '77777777-7777-7777-7777-777777777777',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PARTIAL,
            'container_number' => 'MSCU7777777',
            'replace_existing' => false,
            'total_images' => 2,
            'transferred_count' => 1,
            'failed_count' => 1,
            'error_message' => 'partial failure',
            'manifest' => [
                ['name' => 'ok.jpg', 'status' => 'done', 'error' => null],
                [
                    'name' => 'bad.jpg',
                    'path' => storage_path('app/image-transfers/bad.jpg'),
                    'status' => 'failed',
                    'error' => 'upload timeout',
                ],
            ],
            'staging_dir' => storage_path('app/image-transfers/retry-staging'),
            'finished_at' => now(),
        ]);

        $this->postJson('/api/admin/image-transfers/'.$job->uuid.'/retry')
            ->assertOk()
            ->assertJsonPath('data.transferred_count', 1);

        $job->refresh();

        $this->assertSame(1, $job->transferred_count);
        $this->assertSame(1, $job->failed_count);
        $this->assertContains($job->status, [
            ImageTransferJob::STATUS_PARTIAL,
            ImageTransferJob::STATUS_FAILED,
            ImageTransferJob::STATUS_QUEUED,
            ImageTransferJob::STATUS_PROCESSING,
        ]);

        $bad = collect($job->manifest)->firstWhere('name', 'bad.jpg');
        $this->assertNotNull($bad);
        $this->assertNotSame('upload timeout', $bad['error'] ?? null);
    }

    public function test_queue_failure_marks_transfer_failed(): void
    {
        $job = ImageTransferJob::query()->create([
            'uuid' => '88888888-8888-8888-8888-888888888888',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'container_number' => 'MSCU8888888',
            'replace_existing' => false,
            'total_images' => 1,
            'transferred_count' => 0,
            'failed_count' => 0,
            'manifest' => [],
        ]);

        $batch = new ProcessImageTransferBatch($job->id);
        $batch->failed(new \RuntimeException('queue worker crashed'));

        $job->refresh();

        $this->assertSame(ImageTransferJob::STATUS_FAILED, $job->status);
        $this->assertStringContainsString('queue worker crashed', (string) $job->error_message);
    }

    public function test_list_marks_stale_active_jobs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => '99999999-9999-9999-9999-999999999999',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_PROCESSING,
            'container_number' => 'MSCU9999999',
            'replace_existing' => false,
            'total_images' => 2,
            'transferred_count' => 0,
            'failed_count' => 0,
            'manifest' => [],
        ]);

        ImageTransferJob::query()->whereKey($job->id)->update([
            'updated_at' => now()->subMinutes(ImageTransferJob::STALE_AFTER_MINUTES + 1),
        ]);

        $this->getJson('/api/admin/image-transfers')
            ->assertOk()
            ->assertJsonPath('meta.active_count', 1)
            ->assertJsonPath('meta.stale_count', 1)
            ->assertJsonPath('data.0.is_stale', true);
    }

    public function test_cannot_cancel_finished_transfer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $job = ImageTransferJob::query()->create([
            'uuid' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'type' => ImageTransferJob::TYPE_CONTAINER_ZIP,
            'status' => ImageTransferJob::STATUS_COMPLETED,
            'container_number' => 'MSCUAAAAAAA',
            'replace_existing' => false,
            'total_images' => 1,
            'transferred_count' => 1,
            'failed_count' => 0,
            'manifest' => [],
            'finished_at' => now(),
        ]);

        $this->postJson('/api/admin/image-transfers/'.$job->uuid.'/cancel')
            ->assertStatus(422);
    }
}
