<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionApiProviderRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'url', 'max:500'],
            'api_key' => ['required', 'string', 'max:2000'],
            'monthly_quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_enabled' => ['sometimes', 'boolean'],
            'activate' => ['sometimes', 'boolean'],
        ];
    }
}
