<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaQueueSettingsRequest extends FormRequest
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
            'wa_queue_base_url' => ['nullable', 'string', 'max:500'],
            'wa_queue_sender_id' => ['nullable', 'integer', 'min:1'],
            'wa_queue_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
