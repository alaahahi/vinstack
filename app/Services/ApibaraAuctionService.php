<?php

namespace App\Services;

use App\Exceptions\ApibaraAuctionException;
use App\Models\AuctionApiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        'type',
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
        protected AuctionApiProviderService $providers,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function search(array $filters = []): array
    {
        $forceRefresh = (bool) ($filters['force_refresh'] ?? false);
        $cacheOnly = (bool) ($filters['cache_only'] ?? false);
        unset($filters['force_refresh'], $filters['cache_only']);

        return $this->request(
            'GET',
            '/vehicles',
            $this->normalizeSearchFilters($filters),
            $forceRefresh,
            $cacheOnly,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hasSearchCache(array $filters): bool
    {
        if (! (bool) config('apibara.cache_enabled', true)) {
            return false;
        }

        unset($filters['force_refresh'], $filters['cache_only']);

        return Cache::has($this->cacheKey('GET', '/vehicles', $this->normalizeSearchFilters($filters)));
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
        return $this->requestWithIdentifierFallbacks($identifier, function (string $encoded) use ($forceRefresh) {
            return $this->request('GET', "/vehicles/{$encoded}", [], $forceRefresh);
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function history(string $identifier, array $query = [], bool $forceRefresh = false): array
    {
        $maxPerPage = max(1, (int) config('apibara.max_per_page', 10));
        $params = array_filter([
            'per_page' => isset($query['per_page'])
                ? max(1, min($maxPerPage, (int) $query['per_page']))
                : null,
            'cursor' => isset($query['cursor']) ? (string) $query['cursor'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->requestWithIdentifierFallbacks($identifier, function (string $encoded) use ($params, $forceRefresh) {
            return $this->request('GET', "/vehicles/{$encoded}/history", $params, $forceRefresh);
        });
    }

    /**
     * Related vehicles (same make/story) from Apibara.
     *
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function related(string $identifier, array $query = [], bool $forceRefresh = false): array
    {
        $maxPerPage = max(1, (int) config('apibara.max_per_page', 10));
        $params = array_filter([
            'per_page' => isset($query['per_page'])
                ? max(1, min($maxPerPage, (int) $query['per_page']))
                : min(10, $maxPerPage),
        ], fn ($value) => $value !== null && $value !== '');

        $result = $this->requestWithIdentifierFallbacks($identifier, function (string $encoded) use ($params, $forceRefresh) {
            return $this->request('GET', "/vehicles/{$encoded}/related", $params, $forceRefresh);
        });

        $payload = is_array($result['data'] ?? null) ? $result['data'] : [];
        $upcoming = array_values(array_filter(
            is_array($payload['upcoming'] ?? null) ? $payload['upcoming'] : [],
            'is_array',
        ));
        $past = array_values(array_filter(
            is_array($payload['past'] ?? null) ? $payload['past'] : [],
            'is_array',
        ));

        $result['data'] = [
            'source' => is_array($payload['source'] ?? null) ? $payload['source'] : null,
            'upcoming' => $upcoming,
            'past' => $past,
            'items' => array_values(array_merge(
                array_map(static fn (array $row) => $row + ['_related_group' => 'upcoming'], $upcoming),
                array_map(static fn (array $row) => $row + ['_related_group' => 'past'], $past),
            )),
        ];
        $result['meta'] = array_merge(is_array($result['meta'] ?? null) ? $result['meta'] : [], [
            'upcoming_count' => count($upcoming),
            'past_count' => count($past),
            'total' => count($upcoming) + count($past),
        ]);

        return $result;
    }

    /**
     * slug_vin often 404s; VIN / lot_number work. Try sensible fallbacks.
     *
     * @param  callable(string): array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}  $callback
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    protected function requestWithIdentifierFallbacks(string $identifier, callable $callback): array
    {
        $candidates = $this->identifierCandidates($identifier);
        $lastException = null;

        foreach ($candidates as $candidate) {
            try {
                return $callback($this->encodeIdentifier($candidate));
            } catch (ApibaraAuctionException $e) {
                $lastException = $e;

                if ($e->status() !== 404) {
                    throw $e;
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new ApibaraAuctionException(
            'معرّف السيارة مطلوب (VIN أو رقم اللوت).',
            422,
            'apibara_invalid_identifier',
        );
    }

    /**
     * @return list<string>
     */
    protected function identifierCandidates(string $identifier): array
    {
        $identifier = trim($identifier);
        $candidates = [];

        if ($identifier !== '') {
            $candidates[] = $identifier;
        }

        // slug_vin like "2026-kia-k4-lxs-3KPFT4DE8TE273956" → try trailing VIN
        if (str_contains($identifier, '-')) {
            $tail = (string) Str::afterLast($identifier, '-');
            $tail = strtoupper(trim($tail));

            if (strlen($tail) >= 11 && strlen($tail) <= 20 && preg_match('/^[A-HJ-NPR-Z0-9]+$/', $tail)) {
                $candidates[] = $tail;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function remoteUsage(bool $forceRefresh = false): array
    {
        return $this->request('GET', '/usage', [], $forceRefresh);
    }

    /**
     * Filter metadata for searchable selects (makes/models/types/statuses).
     * Cached longer than vehicle search because it changes rarely.
     *
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached?: bool}
     */
    public function filters(bool $forceRefresh = false): array
    {
        $cacheKey = 'apibara:vehicle-filters:v1';
        $cacheEnabled = (bool) config('apibara.cache_enabled', true);
        $ttl = max(3600, (int) config('apibara.filters_cache_ttl', 86400));

        if ($cacheEnabled && ! $forceRefresh && Cache::has($cacheKey)) {
            $payload = Cache::get($cacheKey);
            $normalized = $this->normalizeSuccessPayload(is_array($payload) ? $payload : []);
            $normalized['cached'] = true;

            $this->usage->record(
                Auth::user(),
                '/vehicles/filters',
                'GET',
                [],
                200,
                true,
                false,
                0,
            );

            return $normalized;
        }

        $result = $this->request('GET', '/vehicles/filters', [], true);

        if ($cacheEnabled && ($result['ok'] ?? false)) {
            Cache::put($cacheKey, [
                'ok' => $result['ok'] ?? true,
                'data' => $result['data'] ?? null,
                'meta' => $result['meta'] ?? null,
            ], $ttl);
        }

        $result['cached'] = false;

        return $result;
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
            $keyword = isset($filters['q']) ? trim((string) $filters['q']) : '';
            $vin = isset($filters['vin']) ? trim((string) $filters['vin']) : '';
            $lot = isset($filters['lot_number']) ? trim((string) $filters['lot_number']) : '';

            if ($keyword !== '') {
                $out['s'] = $keyword;
            } elseif ($vin !== '') {
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
    protected function request(
        string $method,
        string $path,
        array $query = [],
        bool $forceRefresh = false,
        bool $cacheOnly = false,
    ): array {
        $cacheKey = $this->cacheKey($method, $path, $query);
        $cacheEnabled = (bool) config('apibara.cache_enabled', true);
        $ttl = max(60, (int) config('apibara.cache_ttl', 86400));
        $activeProviderId = $this->providers->activeSummary()['id'] ?? null;

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
                null,
                is_int($activeProviderId) ? $activeProviderId : null,
            );

            Log::info('Apibara auction cache hit', [
                'path' => $path,
                'query' => $this->safeQuery($query),
            ]);

            return $normalized;
        }

        if ($cacheOnly) {
            throw new ApibaraAuctionException(
                'لا توجد نتيجة مخزّنة لهذا البحث.',
                404,
                'apibara_cache_miss',
            );
        }

        $skipIds = [];
        $lastException = null;

        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                $provider = $this->providers->resolveForLiveRequest($skipIds);
            } catch (ApibaraAuctionException $e) {
                throw $lastException ?? $e;
            }

            try {
                return $this->sendLive($provider, $method, $path, $query, $cacheKey, $cacheEnabled, $ttl);
            } catch (ApibaraAuctionException $e) {
                $lastException = $e;
                $skipIds[] = $provider->id;

                if (in_array($e->errorCode(), ['apibara_rate_limited'], true)) {
                    $this->providers->markExhausted($provider);

                    continue;
                }

                if (in_array($e->errorCode(), ['apibara_unauthorized', 'apibara_forbidden'], true)) {
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException ?? new ApibaraAuctionException(
            'نفدت حصة كل مفاتيح API المزاد لهذا الشهر.',
            429,
            'apibara_quota_exhausted',
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{ok: bool, data: mixed, meta: array<string, mixed>|null, cached: bool}
     */
    protected function sendLive(
        AuctionApiProvider $provider,
        string $method,
        string $path,
        array $query,
        string $cacheKey,
        bool $cacheEnabled,
        int $ttl,
    ): array {
        $apiKey = trim((string) $provider->api_key);

        if ($apiKey === '') {
            throw new ApibaraAuctionException(
                'مفتاح Apibara غير مضبوط. أضف مفتاحاً من إعدادات المزاد.',
                503,
                'apibara_not_configured',
            );
        }

        $url = rtrim((string) $provider->base_url, '/').'/'.ltrim($path, '/');
        $started = microtime(true);
        $maxAttempts = 2;
        $response = null;

        try {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $response = Http::timeout((int) config('apibara.timeout', 30))
                    ->connectTimeout((int) config('apibara.connect_timeout', 10))
                    ->acceptJson()
                    ->withHeaders([
                        'X-API-Key' => $apiKey,
                    ])
                    ->send($method, $url, ['query' => $query]);

                if ($response->successful() || ! $this->shouldRetryUpstream($response) || $attempt === $maxAttempts) {
                    break;
                }

                Log::info('Apibara auction retry after transient upstream error', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'provider_id' => $provider->id,
                ]);

                usleep(150_000);
            }
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
                $provider->id,
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
                $provider->id,
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
            'provider_id' => $provider->id,
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
                null,
                $provider->id,
            );

            $this->providers->rotateIfExhausted($provider->fresh() ?? $provider);

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
            $provider->id,
        );

        throw $exception;
    }

    protected function shouldRetryUpstream(Response $response): bool
    {
        if ($response->status() < 500) {
            return false;
        }

        $message = (string) ($this->extractRemoteMessage($response) ?? '');

        return str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'No such file or directory')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'server has gone away')
            || $message === '';
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
                $this->safePublicMessage($bodyMessage)
                    ?: 'لم يتم العثور على السيارة أو السجل المطلوب في Apibara.',
                404,
                'apibara_not_found',
            ),
            $status === 429 => new ApibaraAuctionException(
                'تم تجاوز حد طلبات Apibara. انتظر قليلاً ثم أعد المحاولة.',
                429,
                'apibara_rate_limited',
            ),
            $status >= 500 => new ApibaraAuctionException(
                $this->friendlyUpstreamMessage($bodyMessage),
                502,
                $this->upstreamErrorCode($bodyMessage),
            ),
            default => new ApibaraAuctionException(
                $this->safePublicMessage($bodyMessage) ?: "فشل طلب Apibara (HTTP {$status}).",
                $status >= 400 && $status < 500 ? $status : 502,
                'apibara_http_error',
            ),
        };
    }

    protected function friendlyUpstreamMessage(?string $remoteMessage): string
    {
        $message = (string) $remoteMessage;

        if (str_contains($message, 'Unknown column') || str_contains($message, 'SQLSTATE[42S22]')) {
            return 'فلتر البحث غير مدعوم حالياً من مزوّد المزاد. أزل الولاية أو غيّر الفلاتر ثم أعد المحاولة.';
        }

        if (
            str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'No such file or directory')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'server has gone away')
        ) {
            return 'خدمة المزاد غير متاحة مؤقتاً (عطل عند المزوّد). أعد المحاولة بعد قليل.';
        }

        return 'خدمة المزاد غير متاحة حالياً. حاول لاحقاً.';
    }

    protected function upstreamErrorCode(?string $remoteMessage): string
    {
        $message = (string) $remoteMessage;

        if (str_contains($message, 'Unknown column') || str_contains($message, 'SQLSTATE[42S22]')) {
            return 'apibara_bad_filter';
        }

        if (
            str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'No such file or directory')
        ) {
            return 'apibara_upstream_db';
        }

        return 'apibara_upstream';
    }

    protected function safePublicMessage(?string $remoteMessage): ?string
    {
        if ($remoteMessage === null || trim($remoteMessage) === '') {
            return null;
        }

        // Never expose SQL / stack traces from upstream to the UI.
        if (
            str_contains($remoteMessage, 'SQLSTATE')
            || str_contains($remoteMessage, 'select * from')
            || str_contains($remoteMessage, 'Connection:')
        ) {
            return null;
        }

        return trim($remoteMessage);
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
