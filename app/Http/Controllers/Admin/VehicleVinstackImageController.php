<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GalleryTokenExpiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleZipImagesRequest;
use App\Models\Vehicle;
use App\Services\VehicleVinstackZipUploadService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class VehicleVinstackImageController extends Controller
{
    public function uploadZip(
        StoreVehicleZipImagesRequest $request,
        Vehicle $vehicle,
        VehicleVinstackZipUploadService $zipUploads,
    ): JsonResponse {
        /** @var \Illuminate\Http\UploadedFile $zip */
        $zip = $request->file('zip');
        $stage = $request->string('stage')->toString();

        try {
            $result = $zipUploads->uploadZip($vehicle, $stage, $zip);
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

        return response()->json([
            'data' => $result,
            'message' => $this->successMessage($uploaded, $failed),
            'failed' => $failed,
        ], $uploaded > 0 ? 201 : 422);
    }

    /**
     * @param  list<array{name?: string, error?: string}>  $failed
     */
    protected function successMessage(int $uploaded, array $failed): string
    {
        $base = $uploaded === 1
            ? 'تم رفع صورة واحدة إلى Vinstack وتحديث المعرض.'
            : "تم رفع {$uploaded} صور إلى Vinstack وتحديث المعرض.";

        if ($failed === []) {
            return $base;
        }

        return $base.' ('.count($failed).' فشلت)';
    }

    protected function arabicErrorMessage(string $code): string
    {
        return match ($code) {
            'invalid_stage' => 'مرحلة الصور غير صالحة.',
            'gallery_api_not_applicable' => 'رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط.',
            'gallery_vehicle_id_missing' => 'لا يوجد معرّف Vinstack لهذه السيارة.',
            'gallery_token_missing' => 'توكن المعرض غير مضبوط — راجع الإعدادات.',
            'gallery_token_expired' => 'توكن API المعرض منتهي — حدّثه من الإعدادات.',
            'zip_no_images' => 'لم يُعثر على صور صالحة داخل ملف ZIP.',
            'zip_too_many_images' => 'عدد الصور داخل ZIP يتجاوز الحد المسموح (100 صورة).',
            'zip_extension_missing' => 'امتداد ZIP غير متوفر على الخادم.',
            'invalid_zip' => 'ملف ZIP تالف أو غير صالح.',
            'zip_extract_failed' => 'تعذّر استخراج الصور من ملف ZIP.',
            'upload_file_unreadable' => 'تعذّر قراءة ملف الصورة أثناء الرفع.',
            default => str_starts_with($code, 'Gallery API error')
                ? 'فشل الرفع إلى Vinstack: '.$code
                : 'تعذّر رفع الصور إلى Vinstack. '.$code,
        };
    }
}
