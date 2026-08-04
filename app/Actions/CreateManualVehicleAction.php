<?php

namespace App\Actions;

use App\Enums\VehicleSource;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use App\Support\VehicleEta;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateManualVehicleAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Vehicle
    {
        $rawFields = $this->rawFieldsForPayload($data);
        $vpic = Arr::get($data, 'vpic');

        $rawData = [
            ...$rawFields,
            'source' => VehicleSource::Manual->value,
            'created_at' => now()->toIso8601String(),
        ];

        if (is_array($vpic) && $vpic !== []) {
            $rawData['vpic'] = $vpic;
        }

        return Vehicle::query()->create([
            'source' => VehicleSource::Manual,
            'vinstack_id' => null,
            'vin' => strtoupper(trim((string) $data['vin'])),
            'make' => $this->titleCase((string) ($data['make'] ?? '')),
            'model' => $this->titleCase((string) ($data['model'] ?? '')),
            'year' => (int) $data['year'],
            'price' => Arr::get($data, 'price'),
            'eta' => VehicleEta::normalize(Arr::get($data, 'eta')),
            'status' => VehicleStatus::Available,
            'images' => [],
            'raw_data' => $rawData,
            'notes' => Arr::get($data, 'notes'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function rawFieldsForPayload(array $data): array
    {
        $keys = [
            'vin',
            'year',
            'make',
            'model',
            'vehicle_type',
            'fuel_type',
            'color',
            'weight',
            'electrification_level',
            'body_class',
            'drive_type',
            'doors',
            'displacement_l',
            'engine_cylinders',
            'engine_hp',
            'engine_model',
            'transmission',
            'plant_country',
            'plant_city',
            'plant_state',
            'manufacturer',
            'series',
            'gvwr',
            'vpic_error',
            'auction',
            'buyer',
            'lot',
            'purchase_date',
            'eta',
            'value',
            'arrived_terminal_date',
            'left_terminal',
            'title_received',
            'shipping_method',
            'delivery_type',
            'container_number',
            'booking_number',
            'loading_point',
            'destination',
            'location',
            'status',
            'title_status',
            'title_type',
            'keys',
        ];

        $raw = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, ['make', 'model'], true)) {
                $raw[$key] = $this->titleCase((string) $value);

                continue;
            }

            if ($key === 'vin') {
                $raw[$key] = strtoupper(trim((string) $value));

                continue;
            }

            if (in_array($key, ['purchase_date', 'eta'], true)) {
                $normalized = VehicleEta::normalize($value);

                if ($normalized !== null) {
                    $raw[$key] = $normalized;
                }

                continue;
            }

            $raw[$key] = $value;
        }

        return $raw;
    }

    protected function titleCase(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return Str::title(strtolower($value));
    }
}
