<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadContainerImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:200'],
            'images.*' => ['required', 'file', 'image', 'max:15360'],
            'metadata' => ['nullable', 'string'],
            'replace' => ['sometimes', 'boolean'],
        ];
    }
}
