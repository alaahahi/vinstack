<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DatabaseBackupServiceDeleteTest extends TestCase
{
    public function test_delete_backup_removes_file_from_storage(): void
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $filename = 'backup-'.now()->format('YmdHis').'.sql';
        $path = $backupDir.DIRECTORY_SEPARATOR.$filename;
        File::put($path, '-- test backup');

        app(DatabaseBackupService::class)->deleteBackup($filename);

        $this->assertFalse(File::exists($path));
    }

    public function test_delete_backup_rejects_path_traversal_filename(): void
    {
        $this->expectException(HttpException::class);

        try {
            app(DatabaseBackupService::class)->deleteBackup('../database.sqlite');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_delete_backup_returns_not_found_for_missing_file(): void
    {
        $this->expectException(HttpException::class);

        try {
            app(DatabaseBackupService::class)->deleteBackup('backup-20990101120000.sql');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
            throw $e;
        }
    }
}
