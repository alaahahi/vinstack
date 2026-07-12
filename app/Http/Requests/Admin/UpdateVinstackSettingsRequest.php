<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVinstackSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'api_base_url' => ['nullable', 'url', 'max:500'],
            'api_token' => ['nullable', 'string', 'max:2000'],
            'gallery_api_base_url' => ['nullable', 'url', 'max:500'],
            'gallery_api_token' => ['nullable', 'string', 'max:2000'],
            'sync_enabled' => ['sometimes', 'boolean'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'cloudinary_cloud_name' => ['nullable', 'string', 'max:120'],
            'cloudinary_api_key' => ['nullable', 'string', 'max:120'],
            'cloudinary_api_secret' => ['nullable', 'string', 'max:500'],
            'cloudinary_upload_preset' => ['nullable', 'string', 'max:120'],
            'cloudinary_folder' => ['nullable', 'string', 'max:200'],
            'image_transfer_async_enabled' => ['sometimes', 'boolean'],
            'image_transfer_batch_size' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
