<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerNotificationLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VinstackSetting;
use App\Support\DealerNotificationEvents;
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
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{page: int, per_page: int, total: int, last_page: int, has_more: bool}
     * }
     */
    public function listPaginated(int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $paginator = DealerNotificationLog::query()
            ->with(['dealer:id,company_name,phone', 'author:id,name'])
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (DealerNotificationLog $row) => $this->serialize($row))
                ->values()
                ->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
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
            event: DealerNotificationEvents::VEHICLE_ASSIGNED,
            uniqueKey: $uniqueKey,
            author: $author,
            vehicleId: $vehicle->id,
            locale: $locale,
        );
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null, skipped?: bool}
     */
    public function notifyVehicleUpdated(
        Vehicle $vehicle,
        ?string $previousStatus,
        string $newStatus,
        ?User $author = null,
        string $source = 'sync',
    ): array {
        $dealer = $this->dealerForVehicle($vehicle);

        if ($dealer === null) {
            return [
                'ok' => false,
                'message' => 'السيارة غير مسندة لتاجر.',
                'skipped' => true,
            ];
        }

        $message = $this->messages->vehicleUpdated($dealer, $vehicle, $previousStatus, $newStatus);
        $uniqueKey = 'vehicle-update-'.$vehicle->id.'-'.md5($previousStatus.'|'.$newStatus).'-'.now()->timestamp;

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $message,
            source: $source,
            event: DealerNotificationEvents::VEHICLE_UPDATED,
            uniqueKey: $uniqueKey,
            author: $author,
            vehicleId: $vehicle->id,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null, skipped?: bool}
     */
    public function notifyVehicleImagesAdded(
        Vehicle $vehicle,
        int $count,
        ?string $stage = null,
        ?User $author = null,
    ): array {
        if ($count < 1) {
            return [
                'ok' => false,
                'message' => 'لا توجد صور لإرسال إشعار عنها.',
                'skipped' => true,
            ];
        }

        $dealer = $this->dealerForVehicle($vehicle);

        if ($dealer === null) {
            return [
                'ok' => false,
                'message' => 'السيارة غير مسندة لتاجر.',
                'skipped' => true,
            ];
        }

        $message = $this->messages->vehicleImagesAdded($dealer, $vehicle, $count, $stage);
        $uniqueKey = 'vehicle-images-'.$vehicle->id.'-'.$count.'-'.now()->timestamp.'-'.Str::random(4);

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $message,
            source: 'gallery',
            event: DealerNotificationEvents::VEHICLE_IMAGES_ADDED,
            uniqueKey: $uniqueKey,
            author: $author,
            vehicleId: $vehicle->id,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    /**
     * @return array{ok: bool, message: string, sent?: int, failed?: int, logs?: list<array<string, mixed>>, errors?: list<array<string, mixed>>, skipped?: bool}
     */
    public function notifyContainerImagesAdded(
        string $containerNumber,
        int $count,
        iterable $dealers,
        ?User $author = null,
    ): array {
        if ($count < 1) {
            return [
                'ok' => false,
                'message' => 'لا توجد صور لإرسال إشعار عنها.',
                'skipped' => true,
            ];
        }

        $uniqueDealers = collect($dealers)
            ->filter(fn ($dealer) => $dealer instanceof Dealer)
            ->unique('id')
            ->values();

        if ($uniqueDealers->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'لا يوجد تجار مرتبطون بهذا الكونتينر.',
                'skipped' => true,
            ];
        }

        $sent = 0;
        $failed = 0;
        $logs = [];
        $errors = [];

        foreach ($uniqueDealers as $dealer) {
            $message = $this->messages->containerImagesAdded($dealer, $containerNumber, $count);
            $uniqueKey = 'container-images-'.$containerNumber.'-dealer-'.$dealer->id.'-'.now()->timestamp;

            $result = $this->dispatchToDealer(
                dealer: $dealer,
                message: $message,
                source: 'container_gallery',
                event: DealerNotificationEvents::CONTAINER_IMAGES_ADDED,
                uniqueKey: $uniqueKey,
                author: $author,
                locale: $this->messages->localeForDealer($dealer),
            );

            if ($result['ok']) {
                $sent++;

                if (isset($result['log'])) {
                    $logs[] = $result['log'];
                }
            } else {
                $failed++;
                $errors[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->company_name,
                    'message' => $result['message'],
                ];
            }
        }

        return [
            'ok' => $sent > 0,
            'message' => $failed === 0
                ? "تم إرسال إشعار صور الكونتينر إلى {$sent} تاجر."
                : "تم الإرسال إلى {$sent} تاجر، وفشل {$failed}.",
            'sent' => $sent,
            'failed' => $failed,
            'logs' => $logs,
            'errors' => $errors,
        ];
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
            event: DealerNotificationEvents::MANUAL_NOTIFICATION,
            uniqueKey: $uniqueKey,
            author: $author,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    /**
     * @return array{ok: bool, message: string, sent?: int, failed?: int, logs?: list<array<string, mixed>>, errors?: list<array<string, mixed>>, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    public function sendManualToAllDealers(string $message, ?User $author = null): array
    {
        $dealers = Dealer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderByDesc('id')
            ->get();

        if ($dealers->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'لا يوجد تجار بأرقام هاتف مسجّلة.',
                'sent' => 0,
                'failed' => 0,
                'logs' => [],
                'errors' => [],
            ];
        }

        $sent = 0;
        $failed = 0;
        $logs = [];
        $errors = [];

        foreach ($dealers as $dealer) {
            $result = $this->sendManualToDealer($dealer, $message, $author);

            if ($result['ok']) {
                $sent++;

                if (isset($result['log'])) {
                    $logs[] = $result['log'];
                }
            } else {
                $failed++;
                $errors[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->company_name,
                    'message' => $result['message'],
                ];
            }
        }

        return [
            'ok' => $sent > 0,
            'message' => $failed === 0
                ? "تم إرسال الإشعار إلى {$sent} تاجر."
                : "تم الإرسال إلى {$sent} تاجر، وفشل {$failed}.",
            'sent' => $sent,
            'failed' => $failed,
            'logs' => $logs,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{ok: bool, message: string, log?: array<string, mixed>, status?: int|null, errors?: array<string, mixed>|null}
     */
    public function sendLoginCredentials(Dealer $dealer, string $password, ?User $author = null): array
    {
        $dealer->loadMissing('user');

        $email = trim((string) ($dealer->user?->email ?: ''));

        if ($email === '') {
            return [
                'ok' => false,
                'message' => 'لا يوجد بريد إلكتروني يمكن إرسال بيانات الدخول إليه.',
            ];
        }

        $message = $this->messages->loginCredentials(
            $dealer,
            $email,
            $password,
            $this->dealerAutoLoginUrl($email, $password),
        );

        $uniqueKey = 'dealer-login-'.$dealer->id.'-'.now()->timestamp;

        return $this->dispatchToDealer(
            dealer: $dealer,
            message: $message,
            source: 'system',
            event: DealerNotificationEvents::LOGIN_CREDENTIALS,
            uniqueKey: $uniqueKey,
            author: $author,
            locale: $this->messages->localeForDealer($dealer),
        );
    }

    public function dealerAutoLoginUrl(string $email, string $password): string
    {
        $base = rtrim((string) config('app.url', url('/')), '/').'/login';

        return $base.'?'.http_build_query([
            'email' => $email,
            'password' => $password,
            'isolated' => '1',
        ], '', '&', PHP_QUERY_RFC3986);
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
        if (! $this->isEventEnabled($event)) {
            return [
                'ok' => false,
                'message' => 'هذا النوع من الإشعارات معطّل في الإعدادات.',
                'skipped' => true,
            ];
        }

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

    protected function isEventEnabled(string $event): bool
    {
        $settings = VinstackSetting::current();
        $events = DealerNotificationEvents::normalize($settings->dealer_notification_events);

        return (bool) ($events[$event] ?? true);
    }

    protected function dealerForVehicle(Vehicle $vehicle): ?Dealer
    {
        $vehicle->loadMissing('activeAssignment.dealer');

        return $vehicle->activeAssignment?->dealer;
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
