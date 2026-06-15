<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $body = trim((string) $this->input('body', ''));
            $hasImage = $this->hasFile('image');

            if ($body === '' && ! $hasImage) {
                $validator->errors()->add('body', 'اكتب رسالة أو أرفق صورة.');
            }
        });
    }
}
