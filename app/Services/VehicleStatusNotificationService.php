<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleStatusNotification;
use App\Support\VehicleRawDataLocations;
use Illuminate\Support\Collection;

class VehicleStatusNotificationService
{
    public function recordIfChanged(
        Vehicle $vehicle,
        ?string $previousStatus,
        ?string $newStatus,
        string $source,
    ): ?VehicleStatusNotification {
        $previous = $this->normalizeStatus($previousStatus);
        $next = $this->normalizeStatus($newStatus);

        if ($next === null || $previous === $next) {
            return null;
        }

        return VehicleStatusNotification::query()->create([
            'vehicle_id' => $vehicle->id,
            'previous_status' => $previous,
            'new_status' => $next,
            'source' => $source,
        ]);
    }

    public function recordFromRawDataChange(
        Vehicle $vehicle,
        array $previousRaw,
        array $nextRaw,
        string $source,
    ): ?VehicleStatusNotification {
        return $this->recordIfChanged(
            $vehicle,
            VehicleRawDataLocations::resolveLogisticsStatus($previousRaw),
            VehicleRawDataLocations::resolveLogisticsStatus($nextRaw),
            $source,
        );
    }

    public function unreadCount(): int
    {
        return VehicleStatusNotification::query()
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listRecent(int $limit = 40): Collection
    {
        return VehicleStatusNotification::query()
            ->with('vehicle')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (VehicleStatusNotification $row) => $this->serialize($row));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listUnreadRecent(int $limit = 40): Collection
    {
        return VehicleStatusNotification::query()
            ->with('vehicle')
            ->whereNull('read_at')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (VehicleStatusNotification $row) => $this->serialize($row));
    }

    public function markRead(int $id): ?array
    {
        $row = VehicleStatusNotification::query()
            ->with('vehicle')
            ->find($id);

        if (! $row) {
            return null;
        }

        $row->markRead();

        return $this->serialize($row->fresh(['vehicle']));
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(VehicleStatusNotification $row): array
    {
        $vehicle = $row->vehicle;

        return [
            'type' => 'status_change',
            'id' => $row->id,
            'vehicle_id' => $row->vehicle_id,
            'previous_status' => $row->previous_status,
            'new_status' => $row->new_status,
            'source' => $row->source,
            'preview' => $this->previewMessage($row),
            'read_at' => $row->read_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'vin' => $vehicle->vin,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'title' => $this->vehicleTitle($vehicle),
            ] : null,
        ];
    }

    protected function previewMessage(VehicleStatusNotification $row): string
    {
        $vehicle = $row->vehicle;
        $label = $vehicle ? $this->vehicleTitle($vehicle) : 'سيارة';
        $vin = trim((string) ($vehicle?->vin ?? ''));

        if ($vin !== '') {
            $label = "{$label} ({$vin})";
        }

        if ($row->previous_status) {
            return "تغيير حالة {$label}: {$row->previous_status} ← {$row->new_status}";
        }

        return "حالة جديدة لـ {$label}: {$row->new_status}";
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

        return $vehicle->vin ?: 'سيارة';
    }

    protected function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $trimmed = trim($status);

        return $trimmed !== '' ? $trimmed : null;
    }
}
