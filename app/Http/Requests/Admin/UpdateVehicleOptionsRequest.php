<?php

namespace App\Http\Requests\Admin;

use App\Support\VehicleOptions;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (VehicleOptions::KEYS as $key) {
            $rules[$key] = ['sometimes', 'array'];
            $rules[$key.'.*'] = ['string', 'max:120'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'shipping_destinations.array' => 'قائمة الوجهات يجب أن تكون مصفوفة.',
            'loading_points.array' => 'قائمة نقاط التحميل يجب أن تكون مصفوفة.',
            'auctions.array' => 'قائمة المزادات يجب أن تكون مصفوفة.',
        ];
    }
}
