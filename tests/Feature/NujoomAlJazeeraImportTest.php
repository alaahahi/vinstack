<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ContainerService;
use App\Services\NujoomAlJazeeraImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class NujoomAlJazeeraImportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_can_preview_and_apply_nujoom_import(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $this->mock(ContainerService::class, function ($mock) {
            $mock->shouldReceive('listForAdmin')->andReturn([]);
        });

        $file = $this->makeSampleSpreadsheet();

        $previewResponse = $this->postJson('/api/admin/vehicles/import/nujoom/preview', [
            'file' => $file,
        ]);

        $previewResponse->assertOk()
            ->assertJsonPath('data.counts.to_add', 1)
            ->assertJsonPath('data.counts.to_update', 0);

        $token = $previewResponse->json('data.preview_token');
        $this->assertNotEmpty($token);

        $applyResponse = $this->postJson('/api/admin/vehicles/import/nujoom/apply', [
            'preview_token' => $token,
            'confirmed' => true,
            'mode' => 'all',
        ]);

        $applyResponse->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.mode', 'all');

        $this->assertDatabaseHas('vehicles', [
            'vin' => '4T1DAACK6SU551977',
            'source' => VehicleSource::NujoomAlJazeera->value,
        ]);
    }

    public function test_apply_updates_only_skips_new_vehicles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        Vehicle::query()->create([
            'source' => VehicleSource::Vinstack,
            'vinstack_id' => 'vs-existing',
            'vin' => '1HGCY2F54SA066635',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2024,
            'status' => VehicleStatus::Available,
            'raw_data' => ['status' => 'Old'],
        ]);

        $this->mock(ContainerService::class, function ($mock) {
            $mock->shouldReceive('listForAdmin')->andReturn([]);
        });

        $file = $this->makeMixedSpreadsheet();

        $previewResponse = $this->postJson('/api/admin/vehicles/import/nujoom/preview', [
            'file' => $file,
        ]);

        $previewResponse->assertOk()
            ->assertJsonPath('data.counts.to_add', 1)
            ->assertJsonPath('data.counts.to_update', 1);

        $token = $previewResponse->json('data.preview_token');

        $applyResponse = $this->postJson('/api/admin/vehicles/import/nujoom/apply', [
            'preview_token' => $token,
            'confirmed' => true,
            'mode' => 'updates_only',
        ]);

        $applyResponse->assertOk()
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.containers_new', 0)
            ->assertJsonPath('data.mode', 'updates_only');

        $this->assertDatabaseHas('vehicles', [
            'vin' => '1HGCY2F54SA066635',
        ]);

        $this->assertDatabaseMissing('vehicles', [
            'vin' => '4T1DAACK6SU551977',
        ]);

        $updated = Vehicle::query()->where('vin', '1HGCY2F54SA066635')->first();
        $this->assertSame('Loading', $updated->raw_data['status']);
    }

    public function test_apply_rejects_expired_preview_token(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/vehicles/import/nujoom/apply', [
            'preview_token' => '22222222-2222-2222-2222-222222222222',
            'confirmed' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_dealer_cannot_access_nujoom_import(): void
    {
        $dealerUser = User::factory()->create(['role' => UserRole::Dealer]);

        Sanctum::actingAs($dealerUser);

        $this->postJson('/api/admin/vehicles/import/nujoom/preview', [
            'file' => $this->makeSampleSpreadsheet(),
        ])->assertForbidden();
    }

    protected function makeSampleSpreadsheet(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys(NujoomAlJazeeraImportService::HEADER_MAP);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            1,
            'TOYOTA CAMRY SE (HYBRID) 2025',
            'Lot# 44711908Vin# 4T1DAACK6SU551977',
            'Miami | IAA',
            'Georgia',
            'MERSIN, TURKEY (TRMER)',
            '2026-06-04',
            9140,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'New',
        ], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'nujoom-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'sample.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    protected function makeMixedSpreadsheet(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys(NujoomAlJazeeraImportService::HEADER_MAP);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            1,
            'HONDA ACCORD HYBRID SPORT 2025',
            'Lot# 44750097Vin# 1HGCY2F54SA066635',
            'Boston | IAA',
            'Georgia',
            'MERSIN, TURKEY (TRMER)',
            '2026-06-04',
            10660,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'Loading',
        ], null, 'A2');
        $sheet->fromArray([
            2,
            'TOYOTA CAMRY SE (HYBRID) 2025',
            'Lot# 44711908Vin# 4T1DAACK6SU551977',
            'Miami | IAA',
            'Georgia',
            'MERSIN, TURKEY (TRMER)',
            '2026-06-04',
            9140,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'New',
        ], null, 'A3');

        $path = tempnam(sys_get_temp_dir(), 'nujoom-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'mixed.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
