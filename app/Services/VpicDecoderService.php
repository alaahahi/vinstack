<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class VpicDecoderService
{
    protected const BASE_URL = 'https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues';

    /**
     * @return array{mapped: array<string, mixed>, vpic: array<string, mixed>, valid: bool, error: ?string}
     */
    public function decode(string $vin): array
    {
        $vin = strtoupper(trim($vin));

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get(self::BASE_URL.'/'.$vin, ['format' => 'json'])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('تعذّر الاتصال بخدمة فك الشاصي (NHTSA). حاول لاحقاً أو أدخل البيانات يدوياً.', 0, $e);
        }

        $results = $response->json('Results');

        if (! is_array($results) || ! isset($results[0]) || ! is_array($results[0])) {
            throw new RuntimeException('استجابة غير صالحة من خدمة فك الشاصي.');
        }

        $row = $results[0];
        $valid = $this->isValid($row);

        return [
            'mapped' => $this->mapToRawData($row),
            'vpic' => $row,
            'valid' => $valid,
            'error' => $valid ? null : trim((string) ($row['ErrorText'] ?? 'تعذّر فك الشاصي')),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function mapToRawData(array $row): array
    {
        $make = $this->titleCase((string) ($row['Make'] ?? ''));
        $model = $this->titleCase((string) ($row['Model'] ?? ''));
        $year = $this->parseYear($row['ModelYear'] ?? null);
        $fuelType = $this->normalizeFuelType($row);

        return array_filter([
            'vin' => strtoupper(trim((string) ($row['VIN'] ?? ''))) ?: null,
            'year' => $year,
            'make' => $make ?: null,
            'model' => $model ?: null,
            'fuel_type' => $fuelType,
            'electrification_level' => $this->stringOrNull($row['ElectrificationLevel'] ?? null),
            'vehicle_type' => $this->stringOrNull($row['VehicleType'] ?? $row['BodyClass'] ?? null),
            'body_class' => $this->stringOrNull($row['BodyClass'] ?? null),
            'drive_type' => $this->stringOrNull($row['DriveType'] ?? null),
            'doors' => $this->stringOrNull($row['Doors'] ?? null),
            'displacement_l' => $this->stringOrNull($row['DisplacementL'] ?? null),
            'engine_cylinders' => $this->stringOrNull($row['EngineCylinders'] ?? null),
            'engine_hp' => $this->stringOrNull($row['EngineHP'] ?? null),
            'engine_model' => $this->stringOrNull($row['EngineModel'] ?? null),
            'transmission' => $this->stringOrNull($row['TransmissionStyle'] ?? null),
            'plant_country' => $this->stringOrNull($row['PlantCountry'] ?? null),
            'plant_city' => $this->stringOrNull($row['PlantCity'] ?? null),
            'plant_state' => $this->stringOrNull($row['PlantState'] ?? null),
            'manufacturer' => $this->stringOrNull($row['Manufacturer'] ?? null),
            'series' => $this->stringOrNull($row['Series'] ?? null),
            'gvwr' => $this->stringOrNull($row['GVWR'] ?? null),
            'vpic_error' => $this->vpicErrorLabel($row),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function isValid(array $row): bool
    {
        $code = trim((string) ($row['ErrorCode'] ?? ''));

        if ($code === '' || $code === '0') {
            return true;
        }

        return str_starts_with($code, '0,') || str_starts_with($code, '0;');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function normalizeFuelType(array $row): ?string
    {
        $primary = trim((string) ($row['FuelTypePrimary'] ?? ''));
        $secondary = trim((string) ($row['FuelTypeSecondary'] ?? ''));

        if ($secondary === '') {
            return $primary !== '' ? $primary : null;
        }

        if (
            strcasecmp($primary, 'Gasoline') === 0
            && strcasecmp($secondary, 'Electric') === 0
        ) {
            return 'Hybrid (Gasoline/Electric)';
        }

        return trim($primary.' / '.$secondary);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function vpicErrorLabel(array $row): ?string
    {
        if ($this->isValid($row)) {
            return null;
        }

        $text = trim((string) ($row['ErrorText'] ?? ''));

        return $text !== '' ? $text : 'VPIC decode warning';
    }

    protected function parseYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $year = (int) $value;

        return $year >= 1900 && $year <= 2100 ? $year : null;
    }

    protected function titleCase(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return Str::title(strtolower($value));
    }

    protected function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
