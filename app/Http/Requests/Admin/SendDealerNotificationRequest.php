<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendDealerNotificationRequest extends FormRequest
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
            'send_to_all' => ['sometimes', 'boolean'],
            'dealer_id' => [
                Rule::requiredIf(fn () => ! $this->boolean('send_to_all')),
                'nullable',
                'integer',
                'exists:dealers,id',
            ],
            'message' => ['required', 'string', 'min:1', 'max:4096'],
        ];
    }
}
