<?php

namespace App\Http\Requests\Admin;

use App\Services\VehicleVinstackZipUploadService;
use App\Support\VehicleImageStages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleZipImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(VehicleImageStages::STAGES)],
            'zip' => [
                'required',
                'file',
                'mimes:zip',
                'max:'.VehicleVinstackZipUploadService::MAX_ZIP_KILOBYTES,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stage.required' => 'يجب تحديد مرحلة الصور (Terminal / Pickup / Destination).',
            'stage.in' => 'مرحلة الصور غير صالحة.',
            'zip.required' => 'يرجى اختيار ملف ZIP.',
            'zip.file' => 'يجب أن يكون المرفق ملفاً صالحاً.',
            'zip.mimes' => 'يُقبل ملف ZIP فقط (.zip).',
            'zip.max' => 'حجم ملف ZIP كبير جداً — الحد الأقصى 50 ميغابايت.',
        ];
    }
}
