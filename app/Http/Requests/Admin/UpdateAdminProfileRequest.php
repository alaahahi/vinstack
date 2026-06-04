<?php

namespace App\Http\Requests\Admin;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => PhoneNormalizer::normalize($this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'name.min' => 'الاسم قصير جداً.',
            'name.max' => 'الاسم طويل جداً.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.min' => 'رقم الهاتف غير صالح.',
            'phone.max' => 'رقم الهاتف طويل جداً.',
        ];
    }
}
