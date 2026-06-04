<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class SystemController extends Controller
{
    public function migrations(): JsonResponse
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $ran = $migrator->getRepository()->getRan();
        $batches = DB::table('migrations')->pluck('batch', 'migration');

        $items = [];

        foreach ($files as $name => $path) {
            $isRan = in_array($name, $ran, true);
            $items[] = [
                'name' => $name,
                'status' => $isRan ? 'ran' : 'pending',
                'batch' => $isRan ? (int) ($batches[$name] ?? 0) : null,
            ];
        }

        $ranCount = count(array_filter($items, fn (array $item) => $item['status'] === 'ran'));
        $pendingCount = count($items) - $ranCount;

        return response()->json([
            'data' => $items,
            'summary' => [
                'ran' => $ranCount,
                'pending' => $pendingCount,
                'total' => count($items),
            ],
        ]);
    }

    public function migrate(): JsonResponse
    {
        $output = new BufferedOutput;

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true], $output);
            $text = trim($output->fetch());

            return response()->json([
                'success' => $exitCode === 0,
                'output' => $text !== '' ? $text : ($exitCode === 0
                    ? 'تم تنفيذ المايغريشن بنجاح.'
                    : 'فشل تنفيذ المايغريشن.'),
            ], $exitCode === 0 ? 200 : 500);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(): JsonResponse
    {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return response()->json([
                'data' => [
                    'content' => '',
                    'message' => 'لا يوجد ملف سجل بعد.',
                    'lines' => 0,
                ],
            ]);
        }

        $chunk = $this->readLogTail($path, 50 * 1024);
        $lines = $chunk === '' ? [] : (preg_split("/\r\n|\n|\r/", $chunk) ?: []);
        $lines = array_slice($lines, -100);
        $content = implode("\n", $lines);

        return response()->json([
            'data' => [
                'content' => $content,
                'lines' => count($lines),
                'message' => $content === '' ? 'السجل فارغ.' : null,
            ],
        ]);
    }

    public function clearLogs(): JsonResponse
    {
        $path = storage_path('logs/laravel.log');

        if (File::exists($path)) {
            File::put($path, '');
        }

        return response()->json([
            'message' => 'تم مسح سجل الأخطاء.',
            'data' => [
                'content' => '',
                'lines' => 0,
                'message' => 'السجل فارغ.',
            ],
        ]);
    }

    private function readLogTail(string $path, int $maxBytes): string
    {
        $size = filesize($path);

        if ($size === false || $size === 0) {
            return '';
        }

        $readFrom = max(0, $size - $maxBytes);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        fseek($handle, $readFrom);
        $chunk = (string) fread($handle, $size - $readFrom);
        fclose($handle);

        if ($readFrom > 0) {
            $newline = strpos($chunk, "\n");

            if ($newline !== false) {
                $chunk = substr($chunk, $newline + 1);
            }
        }

        return $chunk;
    }
}
