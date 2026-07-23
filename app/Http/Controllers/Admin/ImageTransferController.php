<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImageTransferBatch;
use App\Models\ImageTransferJob;
use App\Services\ContainerImageService;
use App\Services\ImageTransferProcessor;
use App\Services\VehicleDetailService;
use App\Services\VinstackGalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max(1, (int) $request->input('per_page', 10)), 50);

        $paginator = ImageTransferJob::query()
            ->with('vehicle:id,vin')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (ImageTransferJob $job) => $job->toApiArray())
                ->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function show(
        string $uuid,
        ContainerImageService $images,
        VinstackGalleryService $gallery,
        VehicleDetailService $details,
    ): JsonResponse {
        $job = ImageTransferJob::query()
            ->with('vehicle')
            ->where('uuid', $uuid)
            ->firstOrFail();
        $payload = $job->toApiArray();

        if (! in_array($job->status, [
            ImageTransferJob::STATUS_COMPLETED,
            ImageTransferJob::STATUS_PARTIAL,
        ], true)) {
            return response()->json(['data' => $payload]);
        }

        if ($job->container_number) {
            $galleryPayload = $images->payloadForContainer((string) $job->container_number);
            $payload['gallery'] = $galleryPayload;
            $payload['uploaded'] = $job->transferred_count;
            $payload['images'] = $galleryPayload['images'] ?? [];
            $payload['byVin'] = $galleryPayload['byVin'] ?? [];
            $payload['unmatched'] = $galleryPayload['unmatched'] ?? [];
            $payload['meta'] = $galleryPayload['meta'] ?? [];
        }

        if ($job->vehicle_id && $job->vehicle) {
            $vehicle = $job->vehicle->fresh() ?? $job->vehicle;
            $payload['gallery'] = $gallery->buildGalleryPayload($vehicle);
            $payload['vehicle'] = $details->build($vehicle, includeAssignment: true);
            $payload['uploaded'] = $job->transferred_count;
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
