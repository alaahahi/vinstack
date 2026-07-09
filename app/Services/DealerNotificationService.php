<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerNotificationLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DealerNotificationService
{
    public function __construct(
        protected WaQueueService $waQueue,
        protected DealerNotificationMessageBuilder $messages,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listRecent(int $limit = 50): Collection
    {
        return DealerNotificationLog::query()
            ->with(['dealer:id,company_name,phone', 'author:id,name'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DealerNotificationLog $row) => $this->serialize($row));
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    public function notifyVehicleAssigned(Dealer $dealer, Vehicle $vehicle, ?User $author = null): array
    {
        $locale = $this->messages->localeForDealer($dealer);
        $message = $this->messages->vehicleAssigned($dealer, $vehicle);
        $uniqueKey = 'assign-vehicle-'.$vehicle->id.'-dealer-'.$dealer->id.'-'.now()->timestamp;

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $message,
            source: 'assignment',
            event: 'dealer.vehicle_assigned',
            uniqueKey: $uniqueKey,
            author: $author,
            vehicleId: $vehicle->id,
            locale: $locale,
        );
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    public function sendManualToDealer(Dealer $dealer, string $message, ?User $author = null): array
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return [
                'ok' => false,
                'message' => 'نص الإشعار مطلوب.',
            ];
        }

        $uniqueKey = 'manual-dealer-'.$dealer->id.'-'.now()->timestamp.'-'.Str::random(6);

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $trimmed,
            source: 'manual',
            event: 'dealer.manual_notification',
            uniqueKey: $uniqueKey,
            author: $author,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    public function sendLoginCredentials(Dealer $dealer, string $password, ?User $author = null): array
    {
        $dealer->loadMissing('user');

        $identifier = trim((string) ($dealer->phone ?: $dealer->user?->email ?: ''));

        if ($identifier === '') {
            return [
                'ok' => false,
                'message' => 'لا توجد بيانات دخول يمكن إرسالها للتاجر.',
            ];
        }

        $message = $this->messages->loginCredentials(
            $dealer,
            $identifier,
            $password,
            rtrim(config('app.url', url('/')), '/').'/login',
        );

        $uniqueKey = 'dealer-login-'.$dealer->id.'-'.now()->timestamp;

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $message,
            source: 'system',
            event: 'dealer.login_credentials',
            uniqueKey: $uniqueKey,
            author: $author,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    protected function dispatchToDealer(
        Dealer $dealer,
        string $message,
        string $source,
        string $event,
        string $uniqueKey,
        ?User $author = null,
        ?int $vehicleId = null,
        ?string $locale = null,
    ): array {
        $phone = $dealer->phone;

        if (! filled($phone)) {
            return [
                'ok' => false,
                'message' => 'التاجر لا يملك رقم هاتف مسجّل.',
            ];
        }

        $result = $this->waQueue->enqueueMessage(
            phone: $phone,
            message: $message,
            source: 'support',
            event: $event,
            recipientName: $dealer->company_name,
            uniqueKey: $uniqueKey,
            createdBy: $author ? 'admin:'.$author->id : 'vinstack-lite',
        );

        $log = DealerNotificationLog::query()->create([
            'dealer_id' => $dealer->id,
            'vehicle_id' => $vehicleId,
            'created_by' => $author?->id,
            'phone' => $this->waQueue->formatQueuePhone($phone) ?? $phone,
            'message' => $message,
            'channel' => 'whatsapp',
            'source' => $source,
            'event' => $event,
            'locale' => $locale,
            'wa_queue_id' => data_get($result, 'data.id'),
            'wa_queue_status' => data_get($result, 'data.status'),
            'wa_queue_response' => $result['data'] ?? null,
            'error_message' => $result['ok'] ? null : $result['message'],
        ]);

        return [
            'ok' => $result['ok'],
            'message' => $result['message'],
            'status' => $result['status'] ?? null,
            'errors' => $result['errors'] ?? null,
            'log' => $this->serialize($log->fresh(['dealer:id,company_name,phone', 'author:id,name'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(DealerNotificationLog $row): array
    {
        return [
            'id' => $row->id,
            'dealer_id' => $row->dealer_id,
            'vehicle_id' => $row->vehicle_id,
            'dealer_name' => $row->dealer?->company_name,
            'phone' => $row->phone,
            'message' => $row->message,
            'channel' => $row->channel,
            'source' => $row->source,
            'event' => $row->event,
            'locale' => $row->locale,
            'wa_queue_id' => $row->wa_queue_id,
            'wa_queue_status' => $row->wa_queue_status,
            'error_message' => $row->error_message,
            'created_at' => $row->created_at?->toIso8601String(),
            'author_name' => $row->author?->name,
            'success' => $row->error_message === null && filled($row->wa_queue_id),
        ];
    }
}
