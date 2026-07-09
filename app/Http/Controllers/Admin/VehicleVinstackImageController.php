<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GalleryTokenExpiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleZipImagesRequest;
use App\Models\Vehicle;
use App\Services\DealerNotificationService;
use App\Services\VehicleVinstackZipUploadService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class VehicleVinstackImageController extends Controller
{
    public function uploadZip(
        StoreVehicleZipImagesRequest $request,
        Vehicle $vehicle,
        VehicleVinstackZipUploadService $zipUploads,
        DealerNotificationService $notifications,
    ): JsonResponse {
        /** @var \Illuminate\Http\UploadedFile $zip */
        $zip = $request->file('zip');
        $stage = $request->string('stage')->toString();

        try {
            $result = $zipUploads->uploadZip($vehicle, $stage, $zip, $request->user());
        } catch (GalleryTokenExpiredException) {
            return response()->json([
                'message' => 'توكن API المعرض منتهي — حدّثه من الإعدادات.',
            ], 401);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $this->arabicErrorMessage($e->getMessage()),
            ], 422);
        }

        $uploaded = (int) ($result['uploaded'] ?? 0);
        $failed = $result['failed'] ?? [];
        $mode = $result['mode'] ?? 'vinstack';

        if ($uploaded > 0) {
            $notifications->notifyVehicleImagesAdded(
                $vehicle,
                $uploaded,
                $stage,
                $request->user(),
            );
        }

        return response()->json([
            'data' => $result,
            'message' => $this->successMessage($uploaded, $failed, $mode),
            'failed' => $failed,
        ], $uploaded > 0 ? 201 : 422);
    }

    /**
     * @param  list<array{name?: string, error?: string}>  $failed
     */
    protected function successMessage(int $uploaded, array $failed, string $mode = 'vinstack'): string
    {
        $base = $uploaded === 1
            ? 'تم رفع صورة واحدة وتحديث المعرض.'
            : "تم رفع {$uploaded} صور وتحديث المعرض.";

        if ($mode === 'cloudinary') {
            $base = $uploaded === 1
                ? 'تعذّر الرفع إلى Vinstack — تم رفع صورة واحدة عبر Cloudinary.'
                : "تعذّر الرفع إلى Vinstack — تم رفع {$uploaded} صور عبر Cloudinary.";
        }

        if ($failed === []) {
            return $base;
        }

        return $base.' ('.count($failed).' فشلت)';
    }

    protected function arabicErrorMessage(string $code): string
    {
        if (str_contains($code, '|cloudinary_not_configured')) {
            $base = trim(explode('|', $code)[0]);

            return $this->translateGalleryApiError($base)
                .' كما أن Cloudinary غير مضبوط — أضف بيانات Cloudinary من الإعدادات لرفع الصور محلياً.';
        }

        if (str_starts_with($code, 'Gallery API error')) {
            return $this->translateGalleryApiError($code);
        }

        return match ($code) {
            'invalid_stage' => 'مرحلة الصور غير صالحة.',
            'gallery_api_not_applicable' => 'رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط.',
            'gallery_vehicle_id_missing' => 'لا يوجد معرّف Vinstack لهذه السيارة.',
            'gallery_token_missing' => 'توكن المعرض غير مضبوط — راجع الإعدادات.',
            'gallery_token_expired' => 'توكن API المعرض منتهي — حدّثه من الإعدادات.',
            'zip_no_images' => 'لم يُعثر على صور صالحة داخل ملف ZIP (jpg, png, webp, gif).',
            'zip_too_many_images' => 'عدد الصور داخل ZIP يتجاوز الحد المسموح (100 صورة).',
            'zip_extension_missing' => 'امتداد ZIP غير متوفر على الخادم.',
            'invalid_zip' => 'ملف ZIP تالف أو غير صالح.',
            'zip_extract_failed' => 'تعذّر استخراج الصور من ملف ZIP.',
            'upload_file_unreadable' => 'تعذّر قراءة ملف الصورة أثناء الرفع.',
            default => 'تعذّر رفع الصور. '.$code,
        };
    }

    protected function translateGalleryApiError(string $code): string
    {
        $normalized = strtolower($code);

        if (str_contains($normalized, 'invalid vehicle')) {
            return 'السيارة غير موجودة في Vinstack — تأكد من VIN أو زامِن السيارة أولاً.';
        }

        if (str_contains($normalized, '(401)') || str_contains($normalized, 'gallery_token_expired')) {
            return 'توكن API المعرض منتهي — حدّثه من الإعدادات.';
        }

        if (str_contains($normalized, '(403)')) {
            return 'لا تملك صلاحية رفع الصور لهذه السيارة في Vinstack.';
        }

        if (str_contains($normalized, '(404)')) {
            return 'السيارة غير موجودة في معرض Vinstack.';
        }

        if (str_starts_with($code, 'Gallery API error')) {
            return 'فشل الرفع إلى Vinstack: '.preg_replace('/^Gallery API error \(\d+\):\s*/', '', $code);
        }

        return $code;
    }
}
