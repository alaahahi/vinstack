<?php

namespace App\Http\Requests\Dealer;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(VehicleStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
