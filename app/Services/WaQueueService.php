<?php

namespace App\Services;

use App\Models\VinstackSetting;
use App\Support\PhoneNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WaQueueService
{
    public function settings(): VinstackSetting
    {
        return VinstackSetting::current();
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings();

        return $settings->wa_queue_enabled
            && filled($settings->wa_queue_base_url);
    }

    public function baseUrl(): ?string
    {
        $url = trim((string) $this->settings()->wa_queue_base_url);

        if ($url === '') {
            return null;
        }

        return rtrim($url, '/');
    }

    /**
     * @return array{ok: bool, message: string, senders?: list<array<string, mixed>>, api?: array<string, mixed>}
     */
    public function probeConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'إعدادات WA Queue غير مكتملة — فعّل الربط وأدخل Base URL.',
            ];
        }

        try {
            $response = $this->client()->get('/senders/monitor');

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                    'senders' => [],
                ];
            }

            $senders = $response->json('senders') ?? [];
            $online = collect($senders)->where('api_connected', true)->count();

            if ($senders === []) {
                return [
                    'ok' => false,
                    'message' => 'تم الاتصال بـ WA Queue لكن لا يوجد مرسل (Sender) مسجّل.',
                    'senders' => [],
                ];
            }

            $senderId = $this->settings()->wa_queue_sender_id;
            $live = null;

            if ($senderId) {
                $live = $this->checkSenderStatus((int) $senderId);
            }

            return [
                'ok' => $online > 0 || ($live['ok'] ?? false),
                'message' => $online > 0
                    ? "الربط يعمل — {$online} مرسل متصل."
                    : ($live['message'] ?? 'المرسلون مسجّلون لكن غير متصلين بـ WhatsApp.'),
                'senders' => $senders,
                'api' => $live['api'] ?? null,
                'sender' => $live['sender'] ?? null,
            ];
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'message' => 'تعذّر الاتصال بخادم WA Queue — تحقق من الرابط والشبكة.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, sender?: array<string, mixed>, api?: array<string, mixed>}
     */
    public function checkSenderStatus(int $senderId): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'WA Queue غير مضبوط.',
            ];
        }

        try {
            $response = $this->client()->post("/senders/{$senderId}/check-status");

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                ];
            }

            $payload = $response->json();
            $connected = (bool) data_get($payload, 'api.connected', data_get($payload, 'sender.api_connected'));

            return [
                'ok' => $connected,
                'message' => $connected
                    ? 'WhatsApp متصل وجاهز للإرسال.'
                    : 'WhatsApp غير متصل — راجع TextMeBot أو المرسل في WA Queue.',
                'sender' => $payload['sender'] ?? null,
                'api' => $payload['api'] ?? null,
            ];
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'message' => 'تعذّر الاتصال بخادم WA Queue.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    public function enqueueMessage(
        string $phone,
        string $message,
        string $source = 'support',
        ?string $event = null,
        ?string $recipientName = null,
        ?string $uniqueKey = null,
        ?string $createdBy = null,
    ): array {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'WA Queue غير مفعّل — راجع إعدادات الإشعارات.',
            ];
        }

        $formattedPhone = $this->formatQueuePhone($phone);

        if ($formattedPhone === null) {
            return [
                'ok' => false,
                'message' => 'رقم هاتف التاجر غير صالح.',
            ];
        }

        $payload = array_filter([
            'phone' => $formattedPhone,
            'message' => Str::limit(trim($message), 4096, ''),
            'source' => $source,
            'event' => $event,
            'recipient_name' => $recipientName,
            'unique_key' => $uniqueKey,
            'created_by' => $createdBy ?? 'vinstack-lite',
            'priority' => 5,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->client()->post('/queue', $payload);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractErrorMessage($response->json(), $response->status()),
                ];
            }

            $data = $response->json('data') ?? $response->json();

            return [
                'ok' => true,
                'message' => 'تمت إضافة الرسالة إلى طابور WA Queue.',
                'data' => is_array($data) ? $data : [],
            ];
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'message' => 'تعذّر الاتصال بخادم WA Queue.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function formatQueuePhone(?string $phone): ?string
    {
        $normalized = PhoneNormalizer::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $normalized) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '964'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    protected function extractErrorMessage(?array $body, int $status): string
    {
        if (is_array($body)) {
            if (filled($body['message'] ?? null)) {
                return (string) $body['message'];
            }

            $errors = $body['errors'] ?? null;

            if (is_array($errors) && $errors !== []) {
                $first = collect($errors)->flatten()->first();

                if (is_string($first) && $first !== '') {
                    return $first;
                }
            }
        }

        return "فشل طلب WA Queue (HTTP {$status}).";
    }
}
