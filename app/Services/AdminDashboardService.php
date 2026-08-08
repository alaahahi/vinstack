<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\DealerNotificationLog;
use App\Models\Vehicle;
use App\Models\VehicleDealerNoteNotification;
use App\Models\VehicleStatusNotification;
use App\Support\VehicleLogisticsStatus;
use App\Support\VehicleRawDataLocations;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(
        protected VehicleMessageService $messages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $totalVehicles = Vehicle::query()->count();

        return [
            'totals' => [
                'vehicles' => $totalVehicles,
                'dealers' => Dealer::query()->count(),
            ],
            'vehicles_added' => $this->vehiclesAddedLastSixMonths(),
            'photos' => $this->photoStats($totalVehicles),
            'notifications' => $this->notificationStats(),
            'whatsapp' => $this->whatsappStats(),
            'loading_points' => $this->loadingPointStats(),
        ];
    }

    /**
     * @return array{total: int, months: list<array{key: string, count: int}>}
     */
    protected function vehiclesAddedLastSixMonths(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $counts = [];

        for ($i = 0; $i < 6; $i++) {
            $counts[$start->copy()->addMonths($i)->format('Y-m')] = 0;
        }

        $rows = Vehicle::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at']);

        foreach ($rows as $row) {
            $key = $row->created_at?->format('Y-m');

            if ($key !== null && array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        $months = [];

        foreach ($counts as $key => $count) {
            $months[] = [
                'key' => $key,
                'count' => $count,
            ];
        }

        return [
            'total' => array_sum($counts),
            'months' => $months,
        ];
    }

    /**
     * @return array{with_photos: int, without_photos: int, with_uploaded: int, without_uploaded: int}
     */
    protected function photoStats(int $totalVehicles): array
    {
        $withUploaded = Vehicle::query()->whereHas('uploadedImages')->count();
        $galleryIds = $this->vehicleIdsWithGalleryImages();

        $withAny = Vehicle::query()
            ->where(function ($query) use ($galleryIds): void {
                $query->whereHas('uploadedImages');

                if ($galleryIds !== []) {
                    $query->orWhereIn('id', $galleryIds);
                }
            })
            ->count();

        return [
            'with_photos' => $withAny,
            'without_photos' => max(0, $totalVehicles - $withAny),
            'with_uploaded' => $withUploaded,
            'without_uploaded' => max(0, $totalVehicles - $withUploaded),
        ];
    }

    /**
     * @return list<int>
     */
    protected function vehicleIdsWithGalleryImages(): array
    {
        $driver = DB::connection()->getDriverName();

        $query = Vehicle::query()->select('id');

        if ($driver === 'sqlite') {
            $query->whereRaw("json_array_length(COALESCE(images, '[]')) > 0");
        } else {
            $query->whereRaw("JSON_LENGTH(COALESCE(images, CAST('[]' AS JSON))) > 0");
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function notificationStats(): array
    {
        $statusTotal = VehicleStatusNotification::query()->count();
        $statusUnread = VehicleStatusNotification::query()->whereNull('read_at')->count();
        $notesUnread = VehicleDealerNoteNotification::query()->whereNull('read_at')->count();
        $chatUnread = $this->messages->unreadCountForViewer(UserRole::Admin);

        return [
            'status_changes' => [
                'total' => $statusTotal,
                'unread' => $statusUnread,
                'read' => max(0, $statusTotal - $statusUnread),
            ],
            'chat_unread' => $chatUnread,
            'dealer_notes_unread' => $notesUnread,
            'unread_total' => $statusUnread + $chatUnread + $notesUnread,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function whatsappStats(): array
    {
        $logs = DealerNotificationLog::query()
            ->get(['wa_queue_status', 'event', 'error_message', 'wa_queue_id']);

        $byStatus = [];
        $byEvent = [];
        $success = 0;
        $failed = 0;

        foreach ($logs as $log) {
            $status = strtolower(trim((string) ($log->wa_queue_status ?: '')));

            if ($status === '') {
                $status = filled($log->error_message) ? 'failed' : 'queued';
            }

            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            $event = filled($log->event) ? (string) $log->event : 'unknown';
            $byEvent[$event] = ($byEvent[$event] ?? 0) + 1;

            $isSuccess = $log->error_message === null && filled($log->wa_queue_id);

            if ($isSuccess) {
                $success++;
            } else {
                $failed++;
            }
        }

        ksort($byStatus);
        ksort($byEvent);

        return [
            'total' => $logs->count(),
            'success' => $success,
            'failed' => $failed,
            'by_status' => $byStatus,
            'by_event' => $byEvent,
        ];
    }

    /**
     * @return list<array{name: string, total: int, statuses: list<array{key: string, count: int}>}>
     */
    protected function loadingPointStats(): array
    {
        $driver = DB::connection()->getDriverName();

        $query = Vehicle::query();

        if ($driver === 'sqlite') {
            $query->select([
                'id',
                DB::raw("json_extract(raw_data, '$.loading_point') as loading_point"),
                DB::raw("json_extract(raw_data, '$.status') as logistics_status"),
            ]);
        } else {
            $query->select([
                'id',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.loading_point')) as loading_point"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.status')) as logistics_status"),
            ]);
        }

        $grouped = [];

        foreach ($query->get() as $row) {
            $name = VehicleRawDataLocations::locationLabel($row->loading_point) ?? 'unspecified';
            $bucket = VehicleLogisticsStatus::bucket(
                is_string($row->logistics_status) ? $row->logistics_status : null,
            );

            if (! isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'total' => 0,
                    'counts' => array_fill_keys(VehicleLogisticsStatus::keys(), 0),
                ];
            }

            $grouped[$name]['total']++;
            $grouped[$name]['counts'][$bucket]++;
        }

        uasort($grouped, fn (array $a, array $b) => $b['total'] <=> $a['total']);

        $points = [];

        foreach (array_slice($grouped, 0, 24) as $point) {
            $statuses = [];

            foreach (VehicleLogisticsStatus::keys() as $key) {
                $statuses[] = [
                    'key' => $key,
                    'count' => $point['counts'][$key],
                ];
            }

            $points[] = [
                'name' => $point['name'],
                'total' => $point['total'],
                'statuses' => $statuses,
            ];
        }

        return $points;
    }
}
