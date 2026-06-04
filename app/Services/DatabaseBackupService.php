<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    public const MAX_UPLOAD_KILOBYTES = 51200;

    public const FILENAME_PATTERN = '/^backup-\d{14}\.sql$/';

    public function backupDirectory(): string
    {
        $dir = storage_path('app/backups');

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir;
    }

    public function driver(): string
    {
        $default = Config::get('database.default', 'sqlite');
        $driver = Config::get("database.connections.{$default}.driver", $default);

        return match ($driver) {
            'sqlite' => 'sqlite',
            'mysql', 'mariadb' => 'mysql',
            default => $driver,
        };
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string, driver: string}
     */
    public function createBackup(): array
    {
        $filename = 'backup-'.now()->format('YmdHis').'.sql';
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        match ($this->driver()) {
            'sqlite' => $this->dumpSqlite($path),
            'mysql' => $this->dumpMysql($path),
            default => throw new RuntimeException('نوع قاعدة البيانات غير مدعوم للنسخ الاحتياطي.'),
        };

        return $this->formatBackupMeta($filename, $path);
    }

    /**
     * @return list<array{filename: string, size: int, size_human: string, created_at: string}>
     */
    public function listBackups(): array
    {
        $dir = $this->backupDirectory();
        $files = File::glob($dir.DIRECTORY_SEPARATOR.'backup-*.sql') ?: [];
        $items = [];

        foreach ($files as $path) {
            $filename = basename($path);

            if (! $this->isValidBackupFilename($filename)) {
                continue;
            }

            $items[] = $this->formatBackupMeta($filename, $path);
        }

        usort($items, fn (array $a, array $b) => strcmp($b['created_at'], $a['created_at']));

        return array_map(fn (array $item) => [
            'filename' => $item['filename'],
            'size' => $item['size'],
            'size_human' => $this->humanSize($item['size']),
            'created_at' => $item['created_at'],
        ], $items);
    }

    public function resolveBackupPath(string $filename): string
    {
        if (! $this->isValidBackupFilename($filename)) {
            abort(422, 'اسم ملف النسخة الاحتياطية غير صالح.');
        }

        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($path)) {
            abort(404, 'ملف النسخة الاحتياطية غير موجود.');
        }

        return $path;
    }

    public function restoreFromFilename(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);
        $this->restoreFromPath($path);
    }

    public function deleteBackup(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);

        if (! File::delete($path)) {
            throw new RuntimeException('تعذر حذف ملف النسخة الاحتياطية.');
        }
    }

    public function restoreFromUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'sql');

        if (! in_array($extension, ['sql', 'txt'], true)) {
            abort(422, 'يجب أن يكون الملف بصيغة .sql');
        }

        $tempPath = $file->getRealPath();

        if ($tempPath === false) {
            abort(422, 'تعذّر قراءة ملف النسخة الاحتياطية.');
        }

        $this->restoreFromPath($tempPath);
    }

    public function isValidBackupFilename(string $filename): bool
    {
        return (bool) preg_match(self::FILENAME_PATTERN, $filename);
    }

    private function restoreFromPath(string $sqlPath): void
    {
        if (! File::exists($sqlPath) || File::size($sqlPath) === 0) {
            abort(422, 'ملف النسخة الاحتياطية فارغ أو غير موجود.');
        }

        match ($this->driver()) {
            'sqlite' => $this->restoreSqlite($sqlPath),
            'mysql' => $this->restoreMysql($sqlPath),
            default => throw new RuntimeException('نوع قاعدة البيانات غير مدعوم للاسترجاع.'),
        };
    }

    private function dumpSqlite(string $outputPath): void
    {
        $dbPath = $this->sqliteDatabasePath();

        if ($this->trySqliteCliDump($dbPath, $outputPath)) {
            return;
        }

        $this->dumpSqliteViaPdo($dbPath, $outputPath);
    }

    private function dumpMysql(string $outputPath): void
    {
        if ($this->tryMysqldump($outputPath)) {
            return;
        }

        throw new RuntimeException(
            'أداة mysqldump غير متوفرة على الخادم. ثبّت MySQL client tools أو نفّذ النسخ الاحتياطي يدوياً.'
        );
    }

    private function restoreSqlite(string $sqlPath): void
    {
        $dbPath = $this->sqliteDatabasePath();
        $tmpPath = $dbPath.'.restore-'.bin2hex(random_bytes(8)).'.tmp';

        $this->disconnectDatabase();

        try {
            $this->deleteSqliteDatabaseFiles($tmpPath);
            $this->createEmptySqliteFile($tmpPath);

            if (! $this->trySqliteCliRestore($tmpPath, $sqlPath)) {
                $this->restoreSqliteViaPdo($tmpPath, $sqlPath);
            }

            $this->replaceSqliteDatabaseFile($dbPath, $tmpPath);
        } catch (Throwable $e) {
            $this->deleteSqliteDatabaseFiles($tmpPath);

            throw $this->wrapRestoreFailure($e);
        } finally {
            $this->reconnectDatabase();
        }
    }

    private function restoreMysql(string $sqlPath): void
    {
        if ($this->tryMysqlImport($sqlPath)) {
            $this->reconnectDatabase();

            return;
        }

        throw new RuntimeException(
            'أداة mysql غير متوفرة على الخادم. ثبّت MySQL client tools لاسترجاع النسخة.'
        );
    }

    private function trySqliteCliDump(string $dbPath, string $outputPath): bool
    {
        if (! $this->commandExists('sqlite3')) {
            return false;
        }

        $result = Process::timeout(300)->run([
            'sqlite3',
            $dbPath,
            '.dump',
        ]);

        if (! $result->successful()) {
            return false;
        }

        File::put($outputPath, $result->output());

        return true;
    }

    private function trySqliteCliRestore(string $dbPath, string $sqlPath): bool
    {
        if (! $this->commandExists('sqlite3')) {
            return false;
        }

        $sql = File::get($sqlPath);
        $result = Process::timeout(300)->input($sql)->run([
            'sqlite3',
            $dbPath,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException(
                trim($result->errorOutput()) ?: 'فشل تنفيذ ملف النسخة الاحتياطية عبر sqlite3.'
            );
        }

        return true;
    }

    private function tryMysqldump(string $outputPath): bool
    {
        if (! $this->commandExists('mysqldump')) {
            return false;
        }

        $config = $this->mysqlConfig();
        $command = [
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--result-file='.$outputPath,
            $config['database'],
        ];

        $env = [];

        if ($config['password'] !== '') {
            $env['MYSQL_PWD'] = $config['password'];
        }

        $result = Process::timeout(300)->env($env)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'فشل إنشاء نسخة MySQL.');
        }

        return true;
    }

    private function tryMysqlImport(string $sqlPath): bool
    {
        if (! $this->commandExists('mysql')) {
            return false;
        }

        $config = $this->mysqlConfig();
        $command = [
            'mysql',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            $config['database'],
        ];

        $env = [];

        if ($config['password'] !== '') {
            $env['MYSQL_PWD'] = $config['password'];
        }

        $sql = File::get($sqlPath);
        $result = Process::timeout(300)->env($env)->input($sql)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'فشل استرجاع MySQL.');
        }

        return true;
    }

    private function dumpSqliteViaPdo(string $dbPath, string $outputPath): void
    {
        if (! File::exists($dbPath)) {
            throw new RuntimeException('ملف قاعدة بيانات SQLite غير موجود.');
        }

        $pdo = new PDO('sqlite:'.$dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $handle = fopen($outputPath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('تعذّر إنشاء ملف النسخة الاحتياطية.');
        }

        fwrite($handle, "-- Vinstack Lite SQLite backup\n");
        fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");

        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createStmt = $pdo->query(
                'SELECT sql FROM sqlite_master WHERE type = \'table\' AND name = '.$pdo->quote($table)
            )->fetchColumn();

            if (is_string($createStmt) && $createStmt !== '') {
                fwrite($handle, $createStmt.";\n");
            }

            $quotedTable = $this->quoteSqliteIdentifier($table);
            $rows = $pdo->query('SELECT * FROM '.$quotedTable);

            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_map(fn (string $col) => $this->quoteSqliteIdentifier($col), array_keys($row));
                $values = array_map(fn ($value) => $this->formatSqliteValue($value), array_values($row));
                fwrite(
                    $handle,
                    'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n"
                );
            }
        }

        fwrite($handle, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
        fclose($handle);
    }

    private function restoreSqliteViaPdo(string $dbPath, string $sqlPath): void
    {
        $pdo = new PDO('sqlite:'.$dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        try {
            $sql = File::get($sqlPath);
            $pdo->exec('PRAGMA foreign_keys = OFF');

            foreach ($this->splitSqlStatements($sql) as $statement) {
                if ($statement === '') {
                    continue;
                }

                $upper = strtoupper(ltrim($statement));

                if (str_starts_with($upper, 'PRAGMA ') || $upper === 'BEGIN' || $upper === 'COMMIT' || $upper === 'ROLLBACK') {
                    try {
                        $pdo->exec($statement);
                    } catch (Throwable) {
                        // Ignore pragma/transaction wrappers from mixed dump formats.
                    }

                    continue;
                }

                try {
                    $pdo->exec($statement);
                } catch (Throwable $e) {
                    throw new RuntimeException('فشل تنفيذ أمر SQL: '.$e->getMessage(), 0, $e);
                }
            }

            $pdo->exec('PRAGMA foreign_keys = ON');
        } finally {
            unset($pdo);
        }
    }

    private function createEmptySqliteFile(string $dbPath): void
    {
        $directory = dirname($dbPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $pdo = new PDO('sqlite:'.$dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        unset($pdo);
    }

    private function replaceSqliteDatabaseFile(string $dbPath, string $restoredPath): void
    {
        if (! File::exists($restoredPath)) {
            throw new RuntimeException('ملف الاسترجاع المؤقت غير موجود بعد تطبيق النسخة.');
        }

        $this->disconnectDatabase();
        $this->deleteSqliteDatabaseFiles($dbPath);

        if (@rename($restoredPath, $dbPath)) {
            return;
        }

        if (! File::copy($restoredPath, $dbPath)) {
            throw new RuntimeException('تعذّر استبدال ملف قاعدة بيانات SQLite بعد الاسترجاع.');
        }

        File::delete($restoredPath);
    }

    private function deleteSqliteDatabaseFiles(string $dbPath): void
    {
        foreach ([$dbPath, $dbPath.'-wal', $dbPath.'-shm', $dbPath.'-journal'] as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    private function disconnectDatabase(): void
    {
        $connection = Config::get('database.default', 'sqlite');
        DB::disconnect($connection);
        DB::purge($connection);
    }

    private function wrapRestoreFailure(Throwable $e): RuntimeException
    {
        if ($e instanceof RuntimeException && str_contains($e->getMessage(), 'لم تُستبدَل قاعدة البيانات')) {
            return $e;
        }

        $detail = trim($e->getMessage());

        return new RuntimeException(
            'تعذّر استكمال استرجاع قاعدة البيانات من ملف النسخة الاحتياطية. '
            .'لم تُستبدَل قاعدة البيانات الحالية — يمكنك إعادة المحاولة بأمان. '
            .'تأكد أن الملف نسخة SQL (.sql) أُنشئت من نفس التطبيق (نسخ احتياطي من الإعدادات).'
            .($detail !== '' ? ' السبب: '.$detail : ''),
            0,
            $e
        );
    }

    /**
     * @return list<string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($inString) {
                if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                    $stringChar = '';
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inString = true;
                $stringChar = $char;

                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                $buffer = '';

                if ($statement !== '' && ! str_starts_with($statement, '--')) {
                    $statements[] = $statement;
                }
            }
        }

        $tail = trim($buffer);

        if ($tail !== '' && ! str_starts_with($tail, '--')) {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function sqliteDatabasePath(): string
    {
        $connection = Config::get('database.default', 'sqlite');
        $database = Config::get("database.connections.{$connection}.database");

        if (! is_string($database) || $database === '') {
            throw new RuntimeException('مسار قاعدة بيانات SQLite غير مُعرّف.');
        }

        if ($database === ':memory:' || str_starts_with($database, 'file:')) {
            throw new RuntimeException('لا يمكن نسخ قاعدة بيانات SQLite في الذاكرة.');
        }

        if (! str_starts_with($database, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $database)) {
            $database = base_path($database);
        }

        return $database;
    }

    /**
     * @return array{host: string, port: string, database: string, username: string, password: string}
     */
    private function mysqlConfig(): array
    {
        $connection = Config::get('database.default', 'mysql');
        $config = Config::get("database.connections.{$connection}", []);

        return [
            'host' => (string) ($config['host'] ?? '127.0.0.1'),
            'port' => (string) ($config['port'] ?? '3306'),
            'database' => (string) ($config['database'] ?? ''),
            'username' => (string) ($config['username'] ?? 'root'),
            'password' => (string) ($config['password'] ?? ''),
        ];
    }

    private function commandExists(string $command): bool
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $result = Process::run([$finder, $command]);

        return $result->successful();
    }

    private function reconnectDatabase(): void
    {
        DB::purge(Config::get('database.default'));
        DB::reconnect();
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function formatSqliteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }

    /**
     * @return array{filename: string, path: string, size: int, created_at: string, driver: string}
     */
    private function formatBackupMeta(string $filename, string $path): array
    {
        $mtime = File::lastModified($path);

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => (int) File::size($path),
            'size_human' => $this->humanSize((int) File::size($path)),
            'created_at' => date('c', $mtime),
            'driver' => $this->driver(),
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
