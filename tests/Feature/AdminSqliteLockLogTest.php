<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\SqliteLockLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use PDOException;
use Tests\TestCase;

class AdminSqliteLockLogTest extends TestCase
{
    use RefreshDatabase;

    private string $logDir;

    private string $statePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = storage_path('framework/testing/sqlite-locks-api-'.uniqid());
        $this->statePath = storage_path('framework/testing/sqlite-lock-state-api-'.uniqid().'.json');

        config([
            'sqlite_lock_log.enabled' => true,
            'sqlite_lock_log.log_path' => $this->logDir,
            'sqlite_lock_log.state_path' => $this->statePath,
        ]);

        SqliteLockLog::forgetEnabledCache();
        File::ensureDirectoryExists($this->logDir);
    }

    protected function tearDown(): void
    {
        SqliteLockLog::forgetEnabledCache();

        if (File::isDirectory($this->logDir)) {
            File::deleteDirectory($this->logDir);
        }

        if (File::exists($this->statePath)) {
            File::delete($this->statePath);
        }

        parent::tearDown();
    }

    public function test_admin_can_read_sqlite_lock_log_stats(): void
    {
        SqliteLockLog::record(
            new PDOException('SQLSTATE[HY000]: General error: 5 database is locked'),
            'soft_fail',
            ['caller' => 'App\\Services\\Demo::run']
        );

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/admin/system/sqlite-lock-log')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.today_total', 1)
            ->assertJsonPath('data.by_caller.0.name', 'App\\Services\\Demo::run');
    }

    public function test_admin_can_disable_and_enable_logging(): void
    {
        Sanctum::actingAs($this->admin());

        $this->putJson('/api/admin/system/sqlite-lock-log', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        $this->assertFalse(SqliteLockLog::isEnabled());

        $this->putJson('/api/admin/system/sqlite-lock-log', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);
    }

    public function test_admin_can_clear_lock_log(): void
    {
        SqliteLockLog::record(
            new PDOException('database is locked'),
            'retry'
        );

        Sanctum::actingAs($this->admin());

        $this->deleteJson('/api/admin/system/sqlite-lock-log')
            ->assertOk()
            ->assertJsonPath('data.today_total', 0);
    }

    public function test_dealer_cannot_access_sqlite_lock_log(): void
    {
        $dealer = User::factory()->create(['role' => UserRole::Dealer]);
        Sanctum::actingAs($dealer);

        $this->getJson('/api/admin/system/sqlite-lock-log')->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }
}
