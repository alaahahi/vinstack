<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDealerNoteNotification;
use Illuminate\Support\Collection;

class VehicleDealerNoteNotificationService
{
    public function recordIfChanged(
        Vehicle $vehicle,
        Dealer $dealer,
        User $author,
        ?string $previousNotes,
        ?string $newNotes,
    ): ?VehicleDealerNoteNotification {
        $message = trim((string) $newNotes);

        if ($message === '') {
            return null;
        }

        if (trim((string) $previousNotes) === $message) {
            return null;
        }

        return VehicleDealerNoteNotification::query()->create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'author_user_id' => $author->id,
            'message' => $message,
        ]);
    }

    public function unreadCount(): int
    {
        return VehicleDealerNoteNotification::query()
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listRecent(int $limit = 30): Collection
    {
        return VehicleDealerNoteNotification::query()
            ->with(['vehicle', 'dealer'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (VehicleDealerNoteNotification $row) => $this->serialize($row));
    }

    public function findForAdmin(int $id): ?array
    {
        $row = VehicleDealerNoteNotification::query()
            ->with(['vehicle', 'dealer', 'author'])
            ->find($id);

        return $row ? $this->serialize($row, detailed: true) : null;
    }

    public function markRead(int $id): ?array
    {
        $row = VehicleDealerNoteNotification::query()
            ->with(['vehicle', 'dealer', 'author'])
            ->find($id);

        if (! $row) {
            return null;
        }

        $row->markRead();

        return $this->serialize($row->fresh(['vehicle', 'dealer', 'author']), detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(VehicleDealerNoteNotification $row, bool $detailed = false): array
    {
        $vehicle = $row->vehicle;
        $dealer = $row->dealer;

        $payload = [
            'id' => $row->id,
            'message' => $row->message,
            'read_at' => $row->read_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
            'dealer_name' => $dealer?->company_name,
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'vin' => $vehicle->vin,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'title' => $this->vehicleTitle($vehicle),
            ] : null,
        ];

        if ($detailed) {
            $payload['author_name'] = $row->author?->name;
        }

        return $payload;
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
}
