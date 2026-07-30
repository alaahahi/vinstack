<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'vin' => [
                'required',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/i',
                Rule::unique('vehicles', 'vin')->ignore($vehicle?->id),
            ],
            'make' => ['required', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'vpic' => ['nullable', 'array'],
            'vehicle_type' => ['nullable', 'string', 'max:120'],
            'fuel_type' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:80'],
            'weight' => ['nullable', 'string', 'max:80'],
            'auction' => ['nullable', 'string', 'max:120'],
            'buyer' => ['nullable', 'string', 'max:120'],
            'lot' => ['nullable', 'string', 'max:80'],
            'purchase_date' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'arrived_terminal_date' => ['nullable', 'date'],
            'left_terminal' => ['nullable', 'date'],
            'title_received' => ['nullable', 'date'],
            'shipping_method' => ['nullable', 'string', 'max:80'],
            'delivery_type' => ['nullable', 'string', 'max:80'],
            'container_number' => ['nullable', 'string', 'max:80'],
            'booking_number' => ['nullable', 'string', 'max:80'],
            'loading_point' => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'title_status' => ['nullable', 'string', 'max:80'],
            'title_type' => ['nullable', 'string', 'max:80'],
            'keys' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.required' => 'رقم الشاصي مطلوب.',
            'vin.size' => 'رقم الشاصي يجب أن يكون 17 حرفاً.',
            'vin.regex' => 'رقم الشاصي غير صالح (لا يُسمح بالحروف I أو O أو Q).',
            'vin.unique' => 'رقم الشاصي مسجّل مسبقاً في النظام.',
            'make.required' => 'الصانع مطلوب.',
            'model.required' => 'الموديل مطلوب.',
            'year.required' => 'سنة الصنع مطلوبة.',
            'year.integer' => 'سنة الصنع يجب أن تكون رقماً.',
            'year.min' => 'سنة الصنع غير صالحة.',
            'year.max' => 'سنة الصنع غير صالحة.',
            'price.numeric' => 'السعر يجب أن يكون رقماً.',
            'purchase_date.date' => 'تاريخ الشراء غير صالح.',
            'eta.date' => 'تاريخ الوصول المتوقع غير صالح.',
            'arrived_terminal_date.date' => 'تاريخ الوصول للمحطة غير صالح.',
            'left_terminal.date' => 'تاريخ مغادرة المحطة غير صالح.',
            'title_received.date' => 'تاريخ استلام السند غير صالح.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('vin')) {
            $this->merge([
                'vin' => strtoupper(trim((string) $this->input('vin'))),
            ]);
        }
    }
}
