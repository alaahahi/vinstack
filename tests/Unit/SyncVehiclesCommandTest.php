<?php

namespace Tests\Unit;

use App\Models\VinstackSetting;
use App\Support\AutoSyncWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncVehiclesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_skips_when_auto_sync_disabled(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.com/api/v1/client',
            'api_token' => 'vk_test_token',
            'sync_enabled' => false,
        ]);

        $exitCode = Artisan::call('vinstack:sync');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Auto sync is disabled',
            Artisan::output()
        );
    }

    public function test_command_skips_during_peak_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 11:00:00', AutoSyncWindow::TIMEZONE));

        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.com/api/v1/client',
            'api_token' => 'vk_test_token',
            'sync_enabled' => true,
        ]);

        $exitCode = Artisan::call('vinstack:sync');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('22:00 and 06:00', Artisan::output());
        $this->assertStringNotContainsString('Syncing vehicles from Vinstack', Artisan::output());
    }

    public function test_command_skips_when_api_token_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 23:15:00', AutoSyncWindow::TIMEZONE));

        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.com/api/v1/client',
            'api_token' => null,
            'sync_enabled' => true,
        ]);

        config(['services.vinstack.token' => null]);

        $exitCode = Artisan::call('vinstack:sync');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'API token is not configured',
            Artisan::output()
        );
    }

    public function test_command_runs_when_forced_even_if_auto_sync_disabled(): void
    {
        VinstackSetting::query()->create([
            'api_base_url' => 'https://app.vinstack.com/api/v1/client',
            'api_token' => 'vk_test_token',
            'sync_enabled' => false,
        ]);

        $exitCode = Artisan::call('vinstack:sync', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString(
            'Syncing vehicles from Vinstack',
            Artisan::output()
        );
    }
}
