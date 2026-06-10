<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadContainerImagesRequest;
use App\Services\CloudinaryService;
use App\Services\ContainerImageService;
use Illuminate\Http\JsonResponse;

class ContainerImageController extends Controller
{
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
    ): JsonResponse {
        if (! $cloudinary->isConfigured()) {
            return response()->json([
                'message' => 'Cloudinary is not configured. Add credentials in Settings or .env.',
            ], 422);
        }

        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = $request->file('images', []);
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

        return response()->json([
            'data' => $payload,
            'message' => $uploaded === 1
                ? '1 image uploaded to Cloudinary.'
                : "{$uploaded} images uploaded to Cloudinary.",
            'failed' => $failed,
        ], $uploaded > 0 ? 201 : 422);
    }
}
