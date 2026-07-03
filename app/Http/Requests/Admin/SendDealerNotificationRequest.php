<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'dealer_id' => ['required', 'integer', 'exists:dealers,id'],
            'message' => ['required', 'string', 'min:1', 'max:4096'],
        ];
    }
}
