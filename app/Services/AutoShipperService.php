<?php

namespace App\Services;

use App\Models\AutoshipperSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AutoShipperService
{
    public const DEFAULT_BASE_URL = 'https://autoshipper.io/api';

    /**
     * @return list<array<string, mixed>>
     */
    public function vehicles(): array
    {
        return $this->list('/sync/vehicles');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function media(): array
    {
        return $this->list('/sync/media');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function vehicleCharges(): array
    {
        return $this->list('/sync/vehicleCharges');
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function list(string $path): array
    {
        return $this->extractList($this->request('get', $path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path): array
    {
        /** @var Response $response */
        $response = $this->client()->{$method}($path);

        if ($response->failed()) {
            $message = $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            throw new RuntimeException(
                "AutoShipper API error ({$response->status()}): {$message}"
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array<string, mixed>>
     */
    protected function extractList(array $json): array
    {
        $data = $json['data'] ?? $json['items'] ?? $json['results'] ?? $json;

        if (! is_array($data)) {
            return [];
        }

        // Paginated wrappers: { data: { data: [] } } or { data: { items: [] } }
        if ($this->looksLikeAssoc($data)) {
            $nested = $data['data'] ?? $data['items'] ?? $data['results'] ?? null;
            if (is_array($nested)) {
                $data = $nested;
            }
        }

        return array_values(array_filter($data, fn ($item) => is_array($item)));
    }

    /**
     * @param  array<mixed>  $data
     */
    protected function looksLikeAssoc(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        return array_keys($data) !== range(0, count($data) - 1);
    }

    protected function client(): PendingRequest
    {
        $settings = AutoshipperSetting::current();

        $baseUrl = $settings->api_base_url
            ?: config('services.autoshipper.base_url')
            ?: self::DEFAULT_BASE_URL;

        $request = Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->timeout(90);

        $token = $settings->api_token;

        if (is_string($token) && trim($token) !== '') {
            $request = $request->withToken(trim($token));
        }

        return $request;
    }
}
