<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAuctionSpotlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return $role === UserRole::Admin || $role === UserRole::Dealer;
    }

    protected function prepareForValidation(): void
    {
        $platform = $this->input('platform');

        if (is_string($platform) && $platform !== '') {
            $normalized = Str::lower(trim($platform));
            $this->merge([
                'platform' => in_array($normalized, ['copart', 'iaai'], true) ? $normalized : null,
            ]);
        }

        $year = $this->input('year');

        if ($year !== null && $year !== '' && is_numeric($year)) {
            $this->merge(['year' => (int) $year]);
        }

        $thumbs = $this->input('thumb_urls');

        if (is_array($thumbs)) {
            $this->merge([
                'thumb_urls' => array_values(array_filter($thumbs, fn ($url) => is_string($url) && $url !== '')),
            ]);
        }

        // Drop heavy nested Apibara payloads — service only needs flat snapshot fields.
        $this->replace(collect($this->all())->except([
            'pricing',
            'location',
            'condition',
            'media',
            'auction',
            'ad',
            'snapshot',
        ])->all());
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
            'thumb_url' => ['nullable', 'string', 'max:2048'],
            'thumb_urls' => ['nullable', 'array', 'max:12'],
            'thumb_urls.*' => ['nullable', 'string', 'max:2048'],
            'current_bid_usd' => ['nullable', 'numeric'],
            'location_display' => ['nullable', 'string', 'max:255'],
            'primary_damage' => ['nullable', 'string', 'max:255'],
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
