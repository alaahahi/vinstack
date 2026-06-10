<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NujoomAlJazeeraImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NujoomAlJazeeraImportController extends Controller
{
    public function preview(Request $request, NujoomAlJazeeraImportService $import): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $result = $import->preview($request->file('file'));

        return response()->json([
            'data' => $result,
            'message' => 'تمت معاينة الملف.',
        ]);
    }

    public function apply(Request $request, NujoomAlJazeeraImportService $import): JsonResponse
    {
        $validated = $request->validate([
            'preview_token' => ['required', 'string', 'uuid'],
            'confirmed' => ['required', 'boolean', 'accepted'],
        ]);

        try {
            $result = $import->apply($validated['preview_token']);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => sprintf(
                'تم الاستيراد: %d جديد، %d تحديث، %d حاوية جديدة.',
                $result['created'],
                $result['updated'],
                $result['containers_new'],
            ),
        ]);
    }
}
