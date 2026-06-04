<?php

namespace App\Actions;

use App\Enums\VehicleSource;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateManualVehicleAction
{
    public function __construct(

        protected CreateManualVehicleAction $creator,

    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Vehicle $vehicle, array $data): Vehicle
    {

        if ($vehicle->source !== VehicleSource::Manual) {

            throw new HttpException(403, 'لا يمكن تعديل سوى السيارات المُدخلة يدوياً.');
        }

        $rawFields = $this->creator->rawFieldsForPayload($data);

        $vpic = Arr::get($data, 'vpic');

        $existingRaw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

        $rawData = [

            ...$existingRaw,

            ...$rawFields,

            'source' => VehicleSource::Manual->value,

            'updated_at' => now()->toIso8601String(),

        ];

        if (is_array($vpic) && $vpic !== []) {

            $rawData['vpic'] = $vpic;

        }

        $vehicle->update([

            'vin' => strtoupper(trim((string) $data['vin'])),

            'make' => $this->titleCase((string) ($data['make'] ?? '')),

            'model' => $this->titleCase((string) ($data['model'] ?? '')),

            'year' => (int) $data['year'],

            'price' => Arr::get($data, 'price'),

            'raw_data' => $rawData,

            'notes' => Arr::get($data, 'notes'),

        ]);

        return $vehicle->fresh();

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
