<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerNotificationLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DealerNotificationService
{
    public function __construct(
        protected WaQueueService $waQueue,
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
     * @return array{ok: bool, message: string, log?: array<string, mixed>}
     */
    public function sendManualToDealer(Dealer $dealer, string $message, ?User $author = null): array
    {
        $phone = $dealer->phone;

        if (! filled($phone)) {
            return [
                'ok' => false,
                'message' => 'التاجر لا يملك رقم هاتف مسجّل.',
            ];
        }

        $trimmed = trim($message);

        if ($trimmed === '') {
            return [
                'ok' => false,
                'message' => 'نص الإشعار مطلوب.',
            ];
        }

        $uniqueKey = 'manual-dealer-'.$dealer->id.'-'.now()->timestamp.'-'.Str::random(6);

        $result = $this->waQueue->enqueueMessage(
            phone: $phone,
            message: $trimmed,
            source: 'support',
            event: 'dealer.manual_notification',
            recipientName: $dealer->company_name,
            uniqueKey: $uniqueKey,
            createdBy: $author ? 'admin:'.$author->id : 'admin',
        );

        $log = DealerNotificationLog::query()->create([
            'dealer_id' => $dealer->id,
            'created_by' => $author?->id,
            'phone' => $this->waQueue->formatQueuePhone($phone) ?? $phone,
            'message' => $trimmed,
            'channel' => 'whatsapp',
            'source' => 'manual',
            'event' => 'dealer.manual_notification',
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
            'dealer_name' => $row->dealer?->company_name,
            'phone' => $row->phone,
            'message' => $row->message,
            'channel' => $row->channel,
            'source' => $row->source,
            'event' => $row->event,
            'wa_queue_id' => $row->wa_queue_id,
            'wa_queue_status' => $row->wa_queue_status,
            'error_message' => $row->error_message,
            'created_at' => $row->created_at?->toIso8601String(),
            'author_name' => $row->author?->name,
            'success' => $row->error_message === null && filled($row->wa_queue_id),
        ];
    }
}
