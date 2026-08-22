<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchAuctionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return $role === UserRole::Admin || $role === UserRole::Dealer;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'string', Rule::in(['copart', 'iaai'])],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'year_from' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vin' => ['nullable', 'string', 'max:32'],
            'lot_number' => ['nullable', 'string', 'max:64'],
            'q' => ['nullable', 'string', 'max:200'],
            'lot_status' => ['nullable', 'string', Rule::in(['All', 'Timed', 'Buy Now'])],
            'lot_sub_status' => ['nullable', 'string', Rule::in(['Open', 'Live', 'Ended'])],
            'state' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:100'],
            'loc_state' => ['nullable', 'string', 'max:10'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10'],
            'cursor' => ['nullable', 'string', 'max:2000'],
            's' => ['nullable', 'string', 'max:200'],
            'force_refresh' => ['nullable', 'boolean'],
            'cache_only' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['force_refresh', 'cache_only'] as $key) {
            if ($this->exists($key)) {
                $this->merge([
                    $key => filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
