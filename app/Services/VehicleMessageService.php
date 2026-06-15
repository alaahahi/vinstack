<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleMessageService
{
    public function __construct(
        protected CloudinaryService $cloudinary,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForVehicle(Vehicle $vehicle, UserRole $viewerRole): Collection
    {
        return VehicleMessage::query()
            ->where('vehicle_id', $vehicle->id)
            ->with(['author.dealer'])
            ->oldest()
            ->get()
            ->map(fn (VehicleMessage $message) => $this->serialize($message, $viewerRole));
    }

    public function send(
        Vehicle $vehicle,
        User $author,
        UserRole $authorRole,
        ?string $body,
        ?UploadedFile $image,
    ): array {
        $text = trim((string) $body);

        if ($text === '' && ! $image) {
            abort(422, 'Message text or image is required.');
        }

        if ($image && ! $this->cloudinary->isConfigured()) {
            abort(422, 'Cloudinary is not configured.');
        }

        $attachmentUrl = null;
        $attachmentPublicId = null;

        if ($image) {
            $upload = $this->uploadAttachment($vehicle, $image);
            $attachmentUrl = $upload['url'];
            $attachmentPublicId = $upload['public_id'];
        }

        $message = VehicleMessage::query()->create([
            'vehicle_id' => $vehicle->id,
            'author_user_id' => $author->id,
            'author_role' => $authorRole,
            'body' => $text !== '' ? $text : null,
            'attachment_url' => $attachmentUrl,
            'attachment_public_id' => $attachmentPublicId,
        ]);

        $message->load(['author.dealer']);

        return $this->serialize($message, $authorRole);
    }

    public function markReadForViewer(Vehicle $vehicle, UserRole $viewerRole): int
    {
        return VehicleMessage::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('author_role', '!=', $viewerRole)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCountForViewer(UserRole $viewerRole, ?int $dealerId = null): int
    {
        $opposite = $viewerRole === UserRole::Admin ? UserRole::Dealer : UserRole::Admin;

        $query = VehicleMessage::query()
            ->where('author_role', $opposite)
            ->whereNull('read_at');

        if ($dealerId !== null) {
            $query->whereHas('vehicle.assignments', function ($q) use ($dealerId) {
                $q->where('dealer_id', $dealerId)->where('is_active', true);
            });
        }

        return $query->count();
    }

    /**
     * @param  list<int>  $vehicleIds
     * @return array<int, int>
     */
    public function unreadCountsByVehicle(array $vehicleIds, UserRole $viewerRole): array
    {
        if ($vehicleIds === []) {
            return [];
        }

        $opposite = $viewerRole === UserRole::Admin ? UserRole::Dealer : UserRole::Admin;

        return VehicleMessage::query()
            ->select('vehicle_id', DB::raw('COUNT(*) as unread_count'))
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('author_role', $opposite)
            ->whereNull('read_at')
            ->groupBy('vehicle_id')
            ->pluck('unread_count', 'vehicle_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listUnreadThreadsForAdmin(int $limit = 40): Collection
    {
        $vehicleIds = VehicleMessage::query()
            ->select('vehicle_id')
            ->where('author_role', UserRole::Dealer)
            ->whereNull('read_at')
            ->groupBy('vehicle_id')
            ->orderByRaw('MAX(id) DESC')
            ->limit($limit)
            ->pluck('vehicle_id');

        if ($vehicleIds->isEmpty()) {
            return collect();
        }

        $vehicles = Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->with(['activeAssignment.dealer'])
            ->get()
            ->keyBy('id');

        return $vehicleIds
            ->map(function (int $vehicleId) use ($vehicles) {
                $vehicle = $vehicles->get($vehicleId);

                if (! $vehicle) {
                    return null;
                }

                $latest = VehicleMessage::query()
                    ->where('vehicle_id', $vehicleId)
                    ->where('author_role', UserRole::Dealer)
                    ->whereNull('read_at')
                    ->latest()
                    ->first();

                $unread = VehicleMessage::query()
                    ->where('vehicle_id', $vehicleId)
                    ->where('author_role', UserRole::Dealer)
                    ->whereNull('read_at')
                    ->count();

                return [
                    'vehicle_id' => $vehicleId,
                    'unread_count' => $unread,
                    'preview' => $latest?->body ?: ($latest?->attachment_url ? 'صورة مرفقة' : ''),
                    'created_at' => $latest?->created_at?->toIso8601String(),
                    'dealer_name' => $vehicle->activeAssignment?->dealer?->company_name,
                    'vehicle' => [
                        'id' => $vehicle->id,
                        'vin' => $vehicle->vin,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'title' => $this->vehicleTitle($vehicle),
                    ],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $vehicles
     * @return array<int, array<string, mixed>>
     */
    public function attachUnreadCounts(array $vehicles, UserRole $viewerRole): array
    {
        $ids = array_values(array_filter(array_map(
            fn (array $vehicle) => isset($vehicle['id']) ? (int) $vehicle['id'] : null,
            $vehicles,
        )));

        $counts = $this->unreadCountsByVehicle($ids, $viewerRole);

        return array_map(function (array $vehicle) use ($counts) {
            $id = (int) ($vehicle['id'] ?? 0);
            $vehicle['unread_messages_count'] = $counts[$id] ?? 0;

            return $vehicle;
        }, $vehicles);
    }

    /**
     * @return array{url: string, public_id: string}
     */
    protected function uploadAttachment(Vehicle $vehicle, UploadedFile $file): array
    {
        $config = $this->cloudinary->resolveConfig();
        $baseFolder = rtrim((string) ($config['folder'] ?? 'vinstack'), '/');
        $folder = "{$baseFolder}/messages/{$vehicle->id}";

        $upload = $this->cloudinary->upload($file, [
            'folder' => $folder,
            'public_id' => Str::uuid()->toString(),
        ]);

        return [
            'url' => $upload['secure_url'] ?: $upload['url'],
            'public_id' => $upload['public_id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(VehicleMessage $message, UserRole $viewerRole): array
    {
        $author = $message->author;
        $isMine = $message->author_role === $viewerRole;
        $displayName = $this->authorDisplayName($message, $author);

        return [
            'id' => $message->id,
            'body' => $message->body,
            'attachment_url' => $message->attachment_url,
            'author_role' => $message->author_role->value,
            'author_name' => $displayName,
            'author_initial' => $this->initial($displayName),
            'is_mine' => $isMine,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function authorDisplayName(VehicleMessage $message, ?User $author): string
    {
        if ($message->author_role === UserRole::Dealer) {
            return $author?->dealer?->company_name
                ?: $author?->name
                ?: 'تاجر';
        }

        return $author?->name ?: 'الإدارة';
    }

    protected function initial(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return '?';
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 1));
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
