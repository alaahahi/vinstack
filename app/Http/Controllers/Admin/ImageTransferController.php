<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageTransferJob;
use App\Services\ContainerImageService;
use App\Services\ImageTransferHealthService;
use App\Services\ImageTransferProcessor;
use App\Services\ImageTransferProgressStore;
use App\Services\VehicleDetailService;
use App\Services\VinstackGalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class ImageTransferController extends Controller
{
    public function index(
        Request $request,
        ImageTransferHealthService $health,
        ImageTransferProgressStore $progress,
    ): JsonResponse {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(max(1, (int) $request->input('per_page', 10)), 50);

        $paginator = ImageTransferJob::query()
            ->with('vehicle:id,vin')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(function (ImageTransferJob $job) use ($progress) {
                    if ($job->isActive()) {
                        $progress->applyToJob($job);
                    }

                    return $job->toApiArray();
                })
                ->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'active_count' => ImageTransferJob::query()
                    ->whereIn('status', [
                        ImageTransferJob::STATUS_QUEUED,
                        ImageTransferJob::STATUS_PROCESSING,
                    ])
                    ->count(),
                'stale_count' => ImageTransferJob::query()
                    ->whereIn('status', [
                        ImageTransferJob::STATUS_QUEUED,
                        ImageTransferJob::STATUS_PROCESSING,
                    ])
                    ->where('updated_at', '<=', now()->subMinutes(ImageTransferJob::STALE_AFTER_MINUTES))
                    ->count(),
                'health' => $health->status(),
            ],
        ]);
    }

    public function show(
        string $uuid,
        ContainerImageService $images,
        VinstackGalleryService $gallery,
        VehicleDetailService $details,
        ImageTransferProgressStore $progress,
    ): JsonResponse {
        $job = ImageTransferJob::query()
            ->with('vehicle')
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($job->isActive()) {
            $progress->applyToJob($job);
        }

        $payload = $job->toApiArray(includeFailedItems: true);

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

        try {
            $job = $processor->retryFailed($job);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'تعذّرت إعادة محاولة النقل: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'data' => $job->toApiArray(includeFailedItems: true),
            'message' => 'أُعيدت محاولة نقل الصور الفاشلة.',
        ]);
    }

    public function processNow(string $uuid, ImageTransferProcessor $processor): JsonResponse
    {
        $job = ImageTransferJob::query()->where('uuid', $uuid)->firstOrFail();

        if ($job->isFinished()) {
            return response()->json([
                'message' => 'المهمة منتهية ولا تحتاج تشغيلًا يدويًا.',
            ], 422);
        }

        $job = $processor->processNow($job);

        return response()->json([
            'data' => $job->toApiArray(includeFailedItems: true),
            'message' => 'تم تشغيل دفعة نقل الآن.',
        ]);
    }

    public function cancel(string $uuid, ImageTransferProcessor $processor): JsonResponse
    {
        $job = ImageTransferJob::query()->where('uuid', $uuid)->firstOrFail();

        if ($job->isFinished()) {
            return response()->json([
                'message' => 'المهمة منتهية مسبقًا.',
            ], 422);
        }

        $job = $processor->cancel($job);

        return response()->json([
            'data' => $job->toApiArray(includeFailedItems: true),
            'message' => 'تم إلغاء مهمة النقل.',
        ]);
    }
}
