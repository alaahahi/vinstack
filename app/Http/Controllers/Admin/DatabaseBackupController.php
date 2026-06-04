<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backups,
    ) {}

    public function backup(): JsonResponse
    {
        try {
            $meta = $this->backups->createBackup();

            return response()->json([
                'message' => 'تم إنشاء نسخة SQL احتياطية.',
                'data' => [
                    'filename' => $meta['filename'],
                    'size' => $meta['size'],
                    'size_human' => $meta['size_human'],
                    'created_at' => $meta['created_at'],
                    'driver' => $meta['driver'],
                    'download_url' => url('/api/admin/system/backups/'.$meta['filename'].'/download'),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->backups->listBackups(),
            'driver' => $this->backups->driver(),
        ]);
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->backups->resolveBackupPath($filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'accepted'],
            'filename' => ['nullable', 'string', 'max:64'],
            'file' => [
                'nullable',
                'file',
                'mimes:sql,txt',
                'max:'.DatabaseBackupService::MAX_UPLOAD_KILOBYTES,
            ],
        ], [
            'confirm.accepted' => 'يجب تأكيد عملية الاسترجاع.',
            'file.max' => 'حجم ملف النسخة يتجاوز الحد المسموح ('.(DatabaseBackupService::MAX_UPLOAD_KILOBYTES / 1024).' ميجابايت).',
        ]);

        if (empty($validated['filename']) && ! $request->hasFile('file')) {
            return response()->json([
                'message' => 'حدّد ملف نسخة من القائمة أو ارفع ملف .sql.',
            ], 422);
        }

        if (! empty($validated['filename']) && $request->hasFile('file')) {
            return response()->json([
                'message' => 'اختر إما ملفاً من القائمة أو رفع ملف، وليس الاثنين معاً.',
            ], 422);
        }

        try {
            if ($request->hasFile('file')) {
                $this->backups->restoreFromUpload($request->file('file'));
            } else {
                $this->backups->restoreFromFilename($validated['filename']);
            }

            return response()->json([
                'message' => 'تم استرجاع قاعدة البيانات من النسخة الاحتياطية.',
                'driver' => $this->backups->driver(),
            ]);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
