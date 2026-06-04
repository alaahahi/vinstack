<?php

namespace App\Http\Requests\Admin;

use App\Services\VehicleUploadedImageService;
use App\Support\VehicleImageStages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleUploadedImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(VehicleImageStages::STAGES)],
            'images' => ['required', 'array', 'min:1', 'max:'.VehicleUploadedImageService::MAX_FILES_PER_REQUEST],
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:'.VehicleUploadedImageService::MAX_FILE_KILOBYTES,
            ],
        ];
    }
}
