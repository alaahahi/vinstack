<?php

namespace App\Http\Requests\Dealer;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDealerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDealer() ?? false;
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
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'اسم الشركة / معرض مطلوب.',
            'company_name.min' => 'اسم الشركة / معرض قصير جداً.',
            'company_name.max' => 'اسم الشركة / معرض طويل جداً.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.min' => 'رقم الهاتف غير صالح.',
            'phone.max' => 'رقم الهاتف طويل جداً.',
        ];
    }
}
