<?php

namespace App\Services;

use App\Exceptions\ApibaraAuctionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApibaraAuctionService
{
    /**
     * Allowed outbound search query keys for Apibara GET /vehicles.
     *
     * @var list<string>
     */
    protected const SEARCH_KEYS = [
        'platform',
        'make',
        'model',
        'year_from',
        'year_to',
        'lot_status',
        'lot_sub_status',
        'loc_state',
        'per_page',
        'cursor',
        's',
    ];

    public function __construct(
        protected ApibaraUsageService $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function search(array $filters = []): array
    {
        $forceRefresh = (bool) ($filters['force_refresh'] ?? false);
        unset($filters['force_refresh']);

        return $this->request('GET', '/vehicles', $this->normalizeSearchFilters($filters), $forceRefresh);
    }

    /**
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function test(): array
    {
        return $this->search([
            'platform' => 'copart',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year_from' => 2020,
            'year_to' => 2026,
            'lot_status' => 'All',
            'per_page' => min(10, (int) config('apibara.max_per_page', 10)),
        ]);
    }

    /**
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function show(string $identifier, bool $forceRefresh = false): array
    {
        $slug = $this->encodeIdentifier($identifier);

        return $this->request('GET', "/vehicles/{$slug}", [], $forceRefresh);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function history(string $identifier, array $query = [], bool $forceRefresh = false): array
    {
        $slug = $this->encodeIdentifier($identifier);
        $maxPerPage = max(1, (int) config('apibara.max_per_page', 10));
        $params = array_filter([
            'per_page' => isset($query['per_page'])
                ? max(1, min($maxPerPage, (int) $query['per_page']))
                : null,
            'cursor' => isset($query['cursor']) ? (string) $query['cursor'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->request('GET', "/vehicles/{$slug}/history", $params, $forceRefresh);
    }

    /**
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function remoteUsage(bool $forceRefresh = false): array
    {
        return $this->request('GET', '/usage', [], $forceRefresh);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeSearchFilters(array $filters): array
    {
        $out = [];
        $maxPerPage = max(1, (int) config('apibara.max_per_page', 10));

        foreach (self::SEARCH_KEYS as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            $value = $filters[$key];

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, ['year_from', 'year_to', 'per_page'], true)) {
                $out[$key] = (int) $value;

                continue;
            }

            if ($key === 'platform') {
                $platform = strtolower(trim((string) $value));

                if (in_array($platform, ['copart', 'iaai'], true)) {
                    $out['platform'] = $platform;
                }

                continue;
            }

            $out[$key] = is_scalar($value) ? trim((string) $value) : $value;
        }

        if (! isset($out['loc_state'])) {
            $state = $filters['state'] ?? $filters['location'] ?? null;

            if (is_scalar($state) && trim((string) $state) !== '') {
                $out['loc_state'] = strtoupper(trim((string) $state));
            }
        }

        if (! isset($out['s'])) {
            $vin = isset($filters['vin']) ? trim((string) $filters['vin']) : '';
            $lot = isset($filters['lot_number']) ? trim((string) $filters['lot_number']) : '';

            if ($vin !== '') {
                $out['s'] = strtoupper($vin);
            } elseif ($lot !== '') {
                $out['s'] = $lot;
            }
        }

        if (! isset($out['per_page'])) {
            $out['per_page'] = min(10, $maxPerPage);
        } else {
            $out['per_page'] = max(1, min($maxPerPage, (int) $out['per_page']));
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached: bool}
     */
    protected function request(string $method, string $path, array $query = [], bool $forceRefresh = false): array
    {
        $apiKey = (string) config('apibara.api_key', '');

        if ($apiKey === '') {
            throw new ApibaraAuctionException(
                'مفتاح Apibara غير مضبوط. أضف APIBARA_API_KEY في ملف .env.',
                503,
                'apibara_not_configured',
            );
        }

        $cacheKey = $this->cacheKey($method, $path, $query);
        $cacheEnabled = (bool) config('apibara.cache_enabled', true);
        $ttl = max(60, (int) config('apibara.cache_ttl', 3600));

        if ($cacheEnabled && ! $forceRefresh && Cache::has($cacheKey)) {
            $payload = Cache::get($cacheKey);
            $normalized = $this->normalizeSuccessPayload(is_array($payload) ? $payload : []);
            $normalized['cached'] = true;

            $this->usage->record(
                Auth::user(),
                $path,
                $method,
                $this->safeQuery($query),
                200,
                true,
                false,
                0,
            );

            Log::info('Apibara auction cache hit', [
                'path' => $path,
                'query' => $this->safeQuery($query),
            ]);

            return $normalized;
        }

        $url = rtrim((string) config('apibara.base_url'), '/').'/'.ltrim($path, '/');
        $started = microtime(true);

        try {
            $response = Http::timeout((int) config('apibara.timeout', 30))
                ->connectTimeout((int) config('apibara.connect_timeout', 10))
                ->acceptJson()
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                ])
                ->send($method, $url, ['query' => $query]);
        } catch (ConnectionException $e) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $this->usage->record(
                Auth::user(),
                $path,
                $method,
                $this->safeQuery($query),
                null,
                false,
                true,
                $elapsedMs,
                'apibara_connection',
            );
            $this->logFailure($path, $query, null, 'connection', $e);

            throw new ApibaraAuctionException(
                'تعذّر الاتصال بخدمة مزاد Apibara. تحقق من الشبكة ثم أعد المحاولة.',
                504,
                'apibara_connection',
                $e,
            );
        } catch (Throwable $e) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $this->usage->record(
                Auth::user(),
                $path,
                $method,
                $this->safeQuery($query),
                null,
                false,
                true,
                $elapsedMs,
                'apibara_unexpected',
            );
            $this->logFailure($path, $query, null, 'unexpected', $e);

            throw new ApibaraAuctionException(
                'حدث خطأ غير متوقع أثناء الاتصال بـ Apibara.',
                500,
                'apibara_unexpected',
                $e,
            );
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        $status = $response->status();

        Log::info('Apibara auction request', [
            'method' => $method,
            'path' => $path,
            'query' => $this->safeQuery($query),
            'status' => $status,
            'elapsed_ms' => $elapsedMs,
            'cached' => false,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            $normalized = $this->normalizeSuccessPayload($json);
            $normalized['cached'] = false;

            if ($cacheEnabled) {
                Cache::put($cacheKey, is_array($json) ? $json : ['data' => $json], $ttl);
            }

            $this->usage->record(
                Auth::user(),
                $path,
                $method,
                $this->safeQuery($query),
                $status,
                false,
                true,
                $elapsedMs,
            );

            return $normalized;
        }

        $exception = $this->mapHttpError($response, $path, $query);

        $this->usage->record(
            Auth::user(),
            $path,
            $method,
            $this->safeQuery($query),
            $status,
            false,
            true,
            $elapsedMs,
            $exception->errorCode(),
        );

        throw $exception;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function cacheKey(string $method, string $path, array $query): string
    {
        ksort($query);

        return 'apibara:'.sha1($method.'|'.$path.'|'.json_encode($query));
    }

    /**
     * @param  mixed  $json
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null}
     */
    protected function normalizeSuccessPayload(mixed $json): array
    {
        if (! is_array($json)) {
            return [
                'ok' => true,
                'data' => $json,
                'meta' => null,
            ];
        }

        $data = $json['data'] ?? $json;
        $meta = isset($json['meta']) && is_array($json['meta']) ? $json['meta'] : null;

        return [
            'ok' => (bool) ($json['ok'] ?? true),
            'data' => $data,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function mapHttpError(Response $response, string $path, array $query): ApibaraAuctionException
    {
        $status = $response->status();
        $bodyMessage = $this->extractRemoteMessage($response);

        $this->logFailure($path, $query, $status, 'http', null, $bodyMessage);

        return match (true) {
            $status === 401 => new ApibaraAuctionException(
                'مفتاح Apibara غير صالح أو منتهي. راجع APIBARA_API_KEY.',
                401,
                'apibara_unauthorized',
            ),
            $status === 403 => new ApibaraAuctionException(
                'تم رفض الوصول من Apibara. تحقق من صلاحيات الخطة أو المفتاح.',
                403,
                'apibara_forbidden',
            ),
            $status === 404 => new ApibaraAuctionException(
                $bodyMessage ?: 'لم يتم العثور على السيارة أو السجل المطلوب في Apibara.',
                404,
                'apibara_not_found',
            ),
            $status === 429 => new ApibaraAuctionException(
                'تم تجاوز حد طلبات Apibara. انتظر قليلاً ثم أعد المحاولة.',
                429,
                'apibara_rate_limited',
            ),
            $status >= 500 => new ApibaraAuctionException(
                'خدمة Apibara غير متاحة حالياً. حاول لاحقاً.',
                502,
                'apibara_upstream',
            ),
            default => new ApibaraAuctionException(
                $bodyMessage ?: "فشل طلب Apibara (HTTP {$status}).",
                $status >= 400 && $status < 500 ? $status : 502,
                'apibara_http_error',
            ),
        };
    }

    protected function extractRemoteMessage(Response $response): ?string
    {
        $json = $response->json();

        if (! is_array($json)) {
            return null;
        }

        foreach (['message', 'error', 'detail'] as $key) {
            if (isset($json[$key]) && is_string($json[$key]) && trim($json[$key]) !== '') {
                return trim($json[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function logFailure(
        string $path,
        array $query,
        ?int $status,
        string $kind,
        ?Throwable $exception = null,
        ?string $remoteMessage = null,
    ): void {
        Log::warning('Apibara auction request failed', [
            'path' => $path,
            'query' => $this->safeQuery($query),
            'status' => $status,
            'kind' => $kind,
            'remote_message' => $remoteMessage,
            'exception' => $exception?->getMessage(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function safeQuery(array $query): array
    {
        $safe = $query;
        unset($safe['api_key'], $safe['X-API-Key'], $safe['x-api-key']);

        return $safe;
    }

    protected function encodeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new ApibaraAuctionException(
                'معرّف السيارة مطلوب (VIN أو رقم اللوت).',
                422,
                'apibara_invalid_identifier',
            );
        }

        return rawurlencode($identifier);
    }
}
