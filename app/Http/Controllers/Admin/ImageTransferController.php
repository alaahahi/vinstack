<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImageTransferBatch;
use App\Models\ImageTransferJob;
use App\Services\ContainerImageService;
use App\Services\ImageTransferProcessor;
use Illuminate\Http\JsonResponse;

class ImageTransferController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = ImageTransferJob::query()
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (ImageTransferJob $job) => $job->toApiArray());

        return response()->json(['data' => $jobs]);
    }

    public function show(string $uuid, ContainerImageService $images): JsonResponse
    {
        $job = ImageTransferJob::query()->where('uuid', $uuid)->firstOrFail();
        $payload = $job->toApiArray();

        if (in_array($job->status, [
            ImageTransferJob::STATUS_COMPLETED,
            ImageTransferJob::STATUS_PARTIAL,
        ], true) && $job->container_number) {
            $gallery = $images->payloadForContainer((string) $job->container_number);
            $payload['gallery'] = $gallery;
            $payload['uploaded'] = $job->transferred_count;
            $payload['images'] = $gallery['images'] ?? [];
            $payload['byVin'] = $gallery['byVin'] ?? [];
            $payload['unmatched'] = $gallery['unmatched'] ?? [];
            $payload['meta'] = $gallery['meta'] ?? [];
        }

        return response()->json(['data' => $payload]);
    }

    public function retry(string $uuid, ImageTransferProcessor $processor): JsonResponse
    {
        $job = ImageTransferJob::query()->where('uuid', $uuid)->firstOrFail();

        if (! in_array($job->status, [
            ImageTransferJob::STATUS_FAILED,
            ImageTransferJob::STATUS_PARTIAL,
        ], true)) {
            return response()->json([
                'message' => 'لا يمكن إعادة محاولة هذه المهمة في حالتها الحالية.',
            ], 422);
        }

        $manifest = $job->manifest ?? [];

        foreach ($manifest as $offset => $item) {
            if (($item['status'] ?? '') === 'failed') {
                $manifest[$offset]['status'] = 'pending';
                $manifest[$offset]['error'] = null;
            }
        }

        $job->manifest = $manifest;
        $job->status = ImageTransferJob::STATUS_QUEUED;
        $job->error_message = null;
        $job->finished_at = null;
        $job->save();

        $more = $processor->processBatch($job->id);

        if ($more) {
            ProcessImageTransferBatch::dispatch($job->id);
        }

        return response()->json([
            'data' => $job->fresh()->toApiArray(),
            'message' => 'أُعيدت محاولة نقل الصور الفاشلة.',
        ]);
    }
}
