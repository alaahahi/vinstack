<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NujoomImportApplyMode;
use App\Http\Controllers\Controller;
use App\Services\NujoomAlJazeeraImportService;
use Illuminate\Validation\Rule;
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
            'mode' => ['sometimes', 'string', Rule::enum(NujoomImportApplyMode::class)],
        ]);

        $mode = isset($validated['mode'])
            ? NujoomImportApplyMode::from($validated['mode'])
            : NujoomImportApplyMode::All;

        try {
            $result = $import->apply($validated['preview_token'], $mode);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => $this->applyResultMessage($result),
        ]);
    }

    /**
     * @param  array{created: int, updated: int, containers_new: int, mode: string}  $result
     */
    protected function applyResultMessage(array $result): string
    {
        return match ($result['mode']) {
            NujoomImportApplyMode::UpdatesOnly->value => sprintf(
                'تم تحديث %d سيارة.',
                $result['updated'],
            ),
            NujoomImportApplyMode::AddOnly->value => sprintf(
                'تمت إضافة %d سيارة جديدة، %d حاوية جديدة.',
                $result['created'],
                $result['containers_new'],
            ),
            default => sprintf(
                'تم الاستيراد: %d جديد، %d تحديث، %d حاوية جديدة.',
                $result['created'],
                $result['updated'],
                $result['containers_new'],
            ),
        };
    }
}
