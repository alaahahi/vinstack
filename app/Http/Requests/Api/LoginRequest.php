<?php

namespace App\Http\Requests\Api;

use App\Support\PhoneNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'email' => ['nullable', 'email'],
            'password' => ['required_with:email', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasPhone = $this->filled('phone');
            $hasEmail = $this->filled('email');
            $hasPassword = $this->filled('password');

            $singleModeMessage = 'استخدم طريقة دخول واحدة فقط.';

            if ($hasPhone && ($hasEmail || $hasPassword)) {
                $validator->errors()->add('phone', $singleModeMessage);
                if ($hasEmail) {
                    $validator->errors()->add('email', $singleModeMessage);
                }

                return;
            }

            if (! $hasPhone && ! $hasEmail) {
                $validator->errors()->add('phone', 'أدخل رقم الهاتف أو البريد الإلكتروني.');
                $validator->errors()->add('email', 'أدخل البريد الإلكتروني أو رقم الهاتف.');

                return;
            }

            if ($hasPassword && ! $hasEmail) {
                $validator->errors()->add('email', 'أدخل البريد الإلكتروني أو رقم الهاتف.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'password.required_with' => 'كلمة المرور مطلوبة.',
        ];
    }
}
