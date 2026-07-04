<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Vehicle;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\Lang;

class DealerNotificationMessageBuilder
{
    public function localeForDealer(Dealer $dealer): string
    {
        $dealer->loadMissing('user');

        return SupportedLocale::normalize($dealer->user?->locale);
    }

    public function vehicleAssigned(Dealer $dealer, Vehicle $vehicle, ?string $companyName = null): string
    {
        $locale = $this->localeForDealer($dealer);
        $company = trim((string) ($companyName ?: config('app.name', 'Vinstack')));

        $lines = [
            Lang::get('notifications.vehicle_assigned.intro', ['company' => $company], $locale),
            '',
        ];

        foreach ($this->vehicleAssignedFields($vehicle, $locale) as $line) {
            $lines[] = $line;
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @return list<string>
     */
    protected function vehicleAssignedFields(Vehicle $vehicle, string $locale): array
    {
        $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];
        $labels = Lang::get('notifications.vehicle_assigned.labels', [], $locale);

        $values = [
            'year' => $raw['year'] ?? $vehicle->year,
            'make' => $this->stringValue($raw['make'] ?? $vehicle->make),
            'model' => $this->stringValue($raw['model'] ?? $vehicle->model),
            'vin' => $this->stringValue($raw['vin'] ?? $vehicle->vin),
            'color' => $this->stringValue($raw['color'] ?? null),
            'lot' => $this->stringValue($raw['lot'] ?? null),
            'destination' => $this->stringValue($raw['destination'] ?? null),
            'status' => $this->stringValue($raw['status'] ?? null),
        ];

        $lines = [];

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $label = $labels[$key] ?? $key;
            $lines[] = "{$label}: {$value}";
        }

        return $lines;
    }

    protected function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
