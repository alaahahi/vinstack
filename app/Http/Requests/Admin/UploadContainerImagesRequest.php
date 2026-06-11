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
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif,bmp',
                'max:15360',
            ],
            'metadata' => ['nullable', 'string'],
            'replace' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'No image files were received. Send multipart/form-data with images[0], images[1], …',
            'images.min' => 'At least one image file is required in each batch.',
            'images.*.required' => 'An image slot in the batch is empty — the file may not have been sent as multipart.',
            'images.*.file' => 'Each image must be uploaded as a file.',
            'images.*.image' => 'Each upload must be a valid image.',
            'images.*.mimes' => 'Supported image types: JPEG, PNG, WebP, GIF, BMP.',
        ];
    }
}
