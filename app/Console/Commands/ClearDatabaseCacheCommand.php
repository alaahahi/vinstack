<?php

namespace App\Console\Commands;

use App\Services\SystemCacheService;
use Illuminate\Console\Command;

class ClearDatabaseCacheCommand extends Command
{
    protected $signature = 'cache:clear-database
                            {--sessions : Also wipe the sessions table (forces re-login)}';

    protected $description = 'Clear Laravel caches and empty database cache/cache_locks tables (SQLite-safe). Sessions are kept unless --sessions is passed.';

    public function handle(SystemCacheService $cache): int
    {
        $result = $cache->clear($this->option('sessions'));

        $tables = $result['database_tables_cleared'] !== []
            ? implode(', ', $result['database_tables_cleared'])
            : 'none';

        $this->info('Database cache cleared successfully.');
        $this->info('تم حذف كاش قاعدة البيانات بنجاح.');
        $this->line('Artisan: '.implode(', ', $result['cleared']));
        $this->line("Tables emptied: {$tables}");
        $this->line('Sessions cleared: '.($result['sessions_cleared'] ? 'yes' : 'no'));
        $this->line('Vehicle index version bumped: '.($result['vehicle_index_version_bumped'] ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
