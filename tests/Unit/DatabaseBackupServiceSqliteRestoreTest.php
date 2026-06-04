<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupServiceSqliteRestoreTest extends TestCase
{
    private string $tempDir = '';

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_sqlite_restore_replaces_database_when_tables_already_exist(): void
    {
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vinstack-restore-'.uniqid('', true);
        File::makeDirectory($this->tempDir);

        $dbPath = $this->tempDir.DIRECTORY_SEPARATOR.'database.sqlite';
        $pdo = new PDO('sqlite:'.$dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE cache (key TEXT PRIMARY KEY, value TEXT)');
        $pdo->exec("INSERT INTO cache (key, value) VALUES ('k', 'old')");
        unset($pdo);

        $sqlPath = $this->tempDir.DIRECTORY_SEPARATOR.'backup.sql';
        File::put($sqlPath, implode("\n", [
            'PRAGMA foreign_keys=OFF;',
            'BEGIN TRANSACTION;',
            'CREATE TABLE cache (key TEXT PRIMARY KEY, value TEXT);',
            "INSERT INTO cache (key, value) VALUES ('k', 'restored');",
            'COMMIT;',
            'PRAGMA foreign_keys=ON;',
        ]));

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $dbPath);

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $filename = 'backup-'.now()->format('YmdHis').'.sql';
        File::copy($sqlPath, $backupDir.DIRECTORY_SEPARATOR.$filename);

        app(DatabaseBackupService::class)->restoreFromFilename($filename);

        $after = new PDO('sqlite:'.$dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $value = $after->query("SELECT value FROM cache WHERE key = 'k'")->fetchColumn();

        $this->assertSame('restored', $value);

        File::delete($backupDir.DIRECTORY_SEPARATOR.$filename);
    }

    public function test_sqlite_restore_failure_does_not_replace_live_database(): void
    {
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vinstack-restore-fail-'.uniqid('', true);
        File::makeDirectory($this->tempDir);

        $dbPath = $this->tempDir.DIRECTORY_SEPARATOR.'database.sqlite';
        $pdo = new PDO('sqlite:'.$dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE cache (key TEXT PRIMARY KEY, value TEXT)');
        $pdo->exec("INSERT INTO cache (key, value) VALUES ('k', 'keep-me')");
        unset($pdo);

        $sqlPath = $this->tempDir.DIRECTORY_SEPARATOR.'backup-bad.sql';
        File::put($sqlPath, 'CREATE TABLE totally_invalid_syntax (;;;');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $dbPath);

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $filename = 'backup-'.now()->format('YmdHis').'.sql';
        File::copy($sqlPath, $backupDir.DIRECTORY_SEPARATOR.$filename);

        try {
            app(DatabaseBackupService::class)->restoreFromFilename($filename);
            $this->fail('Expected restore to fail.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('لم تُستبدَل قاعدة البيانات الحالية', $e->getMessage());
        }

        $after = new PDO('sqlite:'.$dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $value = $after->query("SELECT value FROM cache WHERE key = 'k'")->fetchColumn();

        $this->assertSame('keep-me', $value);

        File::delete($backupDir.DIRECTORY_SEPARATOR.$filename);
    }
}
