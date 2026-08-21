<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuctionFavoriteRequest extends FormRequest
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
            'identifier' => ['nullable', 'string', 'max:191'],
            'slug_vin' => ['nullable', 'string', 'max:191'],
            'vin' => ['nullable', 'string', 'max:32'],
            'lot_number' => ['nullable', 'string', 'max:64'],
            'platform' => ['nullable', 'string', Rule::in(['copart', 'iaai'])],
            'title' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'thumb_url' => ['nullable', 'string', 'max:1000'],
            'current_bid_usd' => ['nullable', 'numeric'],
            'buy_now_usd' => ['nullable', 'numeric'],
            'location_display' => ['nullable', 'string', 'max:255'],
            'primary_damage' => ['nullable', 'string', 'max:255'],
            'auction_at' => ['nullable', 'string', 'max:64'],
            'pricing' => ['nullable', 'array'],
            'location' => ['nullable', 'array'],
            'condition' => ['nullable', 'array'],
            'media' => ['nullable', 'array'],
            'auction' => ['nullable', 'array'],
            'ad' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasId = filled($this->input('identifier'))
                || filled($this->input('slug_vin'))
                || filled($this->input('vin'))
                || filled($this->input('lot_number'));

            if (! $hasId) {
                $validator->errors()->add('identifier', 'Vehicle identifier is required.');
            }
        });
    }
}
