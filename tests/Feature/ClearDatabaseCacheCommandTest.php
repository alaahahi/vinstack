<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClearDatabaseCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_clear_database_command_clears_cache_without_sessions_by_default(): void
    {
        Cache::put('probe_cli_cache_key', 'stale');

        if (Schema::hasTable('cache')) {
            DB::table('cache')->insert([
                'key' => 'test-cli-db-cache-key',
                'value' => 'stale',
                'expiration' => now()->addHour()->getTimestamp(),
            ]);
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'test-session-id',
                'payload' => 'payload',
                'last_activity' => now()->getTimestamp(),
            ]);
        }

        $this->artisan('cache:clear-database')
            ->expectsOutput('Database cache cleared successfully.')
            ->expectsOutput('تم حذف كاش قاعدة البيانات بنجاح.')
            ->assertSuccessful();

        $this->assertNull(Cache::get('probe_cli_cache_key'));

        if (Schema::hasTable('cache')) {
            $this->assertSame(0, DB::table('cache')->count());
        }

        if (Schema::hasTable('sessions')) {
            $this->assertSame(1, DB::table('sessions')->count());
        }
    }

    public function test_cache_clear_database_command_can_clear_sessions_with_flag(): void
    {
        if (! Schema::hasTable('sessions')) {
            $this->markTestSkipped('sessions table is not present in this test database.');
        }

        DB::table('sessions')->insert([
            'id' => 'test-session-id-2',
            'payload' => 'payload',
            'last_activity' => now()->getTimestamp(),
        ]);

        $this->artisan('cache:clear-database', ['--sessions' => true])
            ->assertSuccessful();

        $this->assertSame(0, DB::table('sessions')->count());
    }
}
