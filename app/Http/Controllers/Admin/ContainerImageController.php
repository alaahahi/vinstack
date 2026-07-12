<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadContainerImagesRequest;
use App\Http\Requests\Admin\UploadContainerZipImagesRequest;
use App\Models\ContainerImage;
use App\Services\CloudinaryService;
use App\Services\ContainerImageService;
use App\Services\ContainerService;
use App\Services\ContainerZipUploadService;
use App\Services\DealerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContainerImageController extends Controller
{
    public function cloudinaryStatus(CloudinaryService $cloudinary): JsonResponse
    {
        $result = $cloudinary->probe(false);

        return response()->json([
            'data' => $result,
            'message' => $result['message'],
        ], ($result['ok'] ?? false) ? 200 : 422);
    }

    public function index(string $container, ContainerImageService $images): JsonResponse
    {
        return response()->json([
            'data' => $images->payloadForContainer($container),
        ]);
    }

    public function upload(
        UploadContainerImagesRequest $request,
        string $container,
        ContainerImageService $images,
        CloudinaryService $cloudinary,
        ContainerService $containers,
        DealerNotificationService $notifications,
    ): JsonResponse {
        if (! $cloudinary->isConfigured()) {
            return response()->json([
                'message' => 'Cloudinary is not configured. Add credentials in Settings or .env.',
            ], 422);
        }

        /** @var list<UploadedFile> $files */
        $files = $request->file('images', []);

        if ($files === [] || $files === null) {
            Log::warning('Container image upload received no files', [
                'container' => $container,
                'content_type' => $request->header('Content-Type'),
                'has_metadata' => $request->filled('metadata'),
            ]);

            return response()->json([
                'message' => 'No image files received. The browser must send multipart/form-data with images[].',
            ], 422);
        }

        $metadata = [];

        if ($request->filled('metadata')) {
            $decoded = json_decode($request->string('metadata')->toString(), true);

            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        try {
            $payload = $images->uploadBatch(
                $container,
                $files,
                $metadata,
                $request->boolean('replace', true),
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $uploaded = (int) ($payload['uploaded'] ?? 0);
        $failed = $payload['failed'] ?? [];
        $received = count(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($uploaded === 0 && $failed === []) {
            $failed[] = [
                'index' => 0,
                'name' => 'batch',
                'error' => "Received {$received} file(s) but none were uploaded. Check Cloudinary credentials and logs.",
            ];
        }

        $message = $this->uploadMessage($uploaded, $failed);

        if ($uploaded > 0) {
            $notifications->notifyContainerImagesAdded(
                $images->normalizeContainerNumber($container),
                $uploaded,
                $containers->dealersForContainer($container),
                $request->user(),
            );
        }

        if ($uploaded === 0) {
            Log::warning('Container image upload finished with zero successes', [
                'container' => $container,
                'received' => $received,
                'failed' => $failed,
            ]);
        }

        return response()->json([
            'data' => $payload,
            'message' => $message,
            'failed' => $failed,
            'received' => $received,
        ], $uploaded > 0 ? 201 : 422);
    }

    public function uploadZip(
        UploadContainerZipImagesRequest $request,
        string $container,
        ContainerZipUploadService $zipUploads,
        ContainerImageService $images,
        CloudinaryService $cloudinary,
        ContainerService $containers,
        DealerNotificationService $notifications,
    ): JsonResponse {
        if (! $cloudinary->isConfigured()) {
            return response()->json([
                'message' => 'Cloudinary is not configured. Add credentials in Settings or .env.',
            ], 422);
        }

        /** @var UploadedFile $zip */
        $zip = $request->file('zip');

        try {
            $payload = $zipUploads->uploadZip(
                $container,
                $zip,
                null,
                $request->boolean('replace', true),
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $this->zipErrorMessage($e->getMessage()),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Container ZIP upload failed', [
                'container' => $container,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'تعذّر معالجة ملف ZIP على الخادم.',
            ], 422);
        }

        $uploaded = (int) ($payload['uploaded'] ?? 0);
        $failed = $payload['failed'] ?? [];

        if ($uploaded > 0) {
            $notifications->notifyContainerImagesAdded(
                $images->normalizeContainerNumber($container),
                $uploaded,
                $containers->dealersForContainer($container),
                $request->user(),
            );
        }

        return response()->json([
            'data' => $payload,
            'message' => $this->uploadMessage($uploaded, $failed),
            'failed' => $failed,
        ], $uploaded > 0 ? 201 : 422);
    }

    public function destroy(
        string $container,
        ContainerImage $image,
        ContainerImageService $images,
    ): JsonResponse {
        $result = $images->delete($container, $image);

        return response()->json([
            'data' => $result['payload'],
            'message' => $result['cloudinary_warning'] ?? 'Image removed successfully.',
            'cloudinary_warning' => $result['cloudinary_warning'],
        ]);
    }

    /**
     * @param  list<array{index?: int, name?: string, error?: string}>  $failed
     */
    protected function uploadMessage(int $uploaded, array $failed): string
    {
        if ($uploaded === 0) {
            $detail = $failed[0]['error'] ?? null;

            return $detail
                ? "0 images uploaded to Cloudinary. {$detail}"
                : '0 images uploaded to Cloudinary.';
        }

        return $uploaded === 1
            ? '1 image uploaded to Cloudinary.'
            : "{$uploaded} images uploaded to Cloudinary.";
    }

    protected function zipErrorMessage(string $code): string
    {
        return match ($code) {
            'zip_extension_missing' => 'امتداد ZIP غير متوفر على الخادم.',
            'invalid_zip' => 'ملف ZIP تالف أو غير صالح.',
            'zip_extract_failed' => 'تعذّر استخراج الصور من ملف ZIP.',
            'zip_no_images' => 'لم يُعثر على صور صالحة داخل ملف ZIP (jpg, png, webp, gif, bmp).',
            'zip_too_many_images' => 'عدد الصور داخل ZIP يتجاوز الحد المسموح (200 صورة).',
            default => 'تعذّر رفع ملف ZIP. '.$code,
        };
    }
}
