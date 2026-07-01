<?php

namespace App\Http\Requests\Admin;

use App\Support\VehicleImageStages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderVehicleGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', Rule::in(VehicleImageStages::STAGES)],
            'urls' => ['required', 'array', 'min:1'],
            'urls.*' => ['required', 'string', 'min:1'],
        ];
    }
}
