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

        return SupportedLocale::forNotifications(
            $dealer->user?->locale,
            (bool) $dealer->user?->locale_customized,
        );
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

    public function vehicleUpdated(Dealer $dealer, Vehicle $vehicle, ?string $previousStatus, string $newStatus): string
    {
        $locale = $this->localeForDealer($dealer);
        $company = trim((string) config('app.name', 'Vinstack'));
        $title = $this->vehicleTitle($vehicle);

        $lines = [
            Lang::get('notifications.vehicle_updated.intro', ['company' => $company], $locale),
            Lang::get('notifications.vehicle_updated.vehicle', ['value' => $title], $locale),
        ];

        $vin = trim((string) ($vehicle->vin ?? ''));

        if ($vin !== '') {
            $lines[] = Lang::get('notifications.vehicle_updated.vin', ['value' => $vin], $locale);
        }

        if ($previousStatus) {
            $lines[] = Lang::get('notifications.vehicle_updated.change', [
                'previous' => $previousStatus,
                'next' => $newStatus,
            ], $locale);
        } else {
            $lines[] = Lang::get('notifications.vehicle_updated.change', [
                'previous' => '—',
                'next' => $newStatus,
            ], $locale);
        }

        return trim(implode("\n", $lines));
    }

    public function vehicleImagesAdded(Dealer $dealer, Vehicle $vehicle, int $count, ?string $stage = null): string
    {
        $locale = $this->localeForDealer($dealer);
        $company = trim((string) config('app.name', 'Vinstack'));
        $title = $this->vehicleTitle($vehicle);

        $lines = [
            Lang::get('notifications.vehicle_images_added.intro', ['company' => $company], $locale),
            Lang::get('notifications.vehicle_images_added.vehicle', ['value' => $title], $locale),
            Lang::get('notifications.vehicle_images_added.count', ['count' => $count], $locale),
        ];

        $vin = trim((string) ($vehicle->vin ?? ''));

        if ($vin !== '') {
            $lines[] = Lang::get('notifications.vehicle_images_added.vin', ['value' => $vin], $locale);
        }

        if ($stage) {
            $lines[] = Lang::get('notifications.vehicle_images_added.stage', ['stage' => $stage], $locale);
        }

        return trim(implode("\n", $lines));
    }

    public function containerImagesAdded(Dealer $dealer, string $containerNumber, int $count): string
    {
        $locale = $this->localeForDealer($dealer);
        $company = trim((string) config('app.name', 'Vinstack'));

        return trim(implode("\n", [
            Lang::get('notifications.container_images_added.intro', ['company' => $company], $locale),
            Lang::get('notifications.container_images_added.container', ['value' => $containerNumber], $locale),
            Lang::get('notifications.container_images_added.count', ['count' => $count], $locale),
        ]));
    }

    protected function vehicleTitle(Vehicle $vehicle): string
    {
        $parts = array_filter([
            trim((string) $vehicle->make),
            trim((string) $vehicle->model),
            $vehicle->year ? (string) $vehicle->year : null,
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return trim((string) ($vehicle->vin ?? '')) ?: 'Vehicle';
    }

    public function loginCredentials(Dealer $dealer, string $email, string $password, string $loginUrl): string
    {
        $locale = $this->localeForDealer($dealer);
        $company = trim((string) config('app.name', 'Vinstack'));

        return trim(implode("\n", [
            Lang::get('notifications.login_credentials.welcome', ['company' => $company], $locale),
            '',
            Lang::get('notifications.login_credentials.intro', ['company' => $company], $locale),
            Lang::get('notifications.login_credentials.email', ['value' => $email], $locale),
            Lang::get('notifications.login_credentials.password', ['value' => $password], $locale),
            Lang::get('notifications.login_credentials.url', ['value' => $loginUrl], $locale),
        ]));
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
