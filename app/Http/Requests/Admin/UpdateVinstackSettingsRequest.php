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
        ];
    }
}
