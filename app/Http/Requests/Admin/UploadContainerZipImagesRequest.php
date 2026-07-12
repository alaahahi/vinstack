<?php

namespace App\Http\Requests\Admin;

use App\Services\ContainerZipUploadService;
use Illuminate\Foundation\Http\FormRequest;

class UploadContainerZipImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zip' => [
                'required',
                'file',
                'mimes:zip',
                'max:'.ContainerZipUploadService::MAX_ZIP_KILOBYTES,
            ],
            'replace' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'zip.required' => 'يرجى اختيار ملف ZIP.',
            'zip.file' => 'يجب أن يكون المرفق ملفاً صالحاً.',
            'zip.mimes' => 'يُقبل ملف ZIP فقط (.zip).',
            'zip.max' => 'حجم ملف ZIP كبير جداً — الحد الأقصى 150 ميغابايت.',
        ];
    }
}
