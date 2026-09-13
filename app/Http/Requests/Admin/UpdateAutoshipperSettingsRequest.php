<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutoshipperSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'api_base_url' => ['nullable', 'string', 'max:500'],
            'api_token' => ['nullable', 'string', 'max:2000'],
            'sync_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
