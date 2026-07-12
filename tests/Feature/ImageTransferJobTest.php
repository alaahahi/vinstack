<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ImageTransferJob;
use App\Models\User;
use App\Models\VinstackSetting;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use ZipArchive;

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
            ->assertJsonCount(1, 'data');
    }
}
