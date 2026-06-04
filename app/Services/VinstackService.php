<?php

namespace App\Services;

use App\Models\VinstackSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VinstackService
{
    public const DEFAULT_BASE_URL = 'https://app.vinstack.com/api/v1/client';

    public function autos(): array
    {
        return $this->list('/autos');
    }

    public function auto(string $vin): array
    {
        return $this->one("/autos/{$vin}");
    }

    public function loadingLists(): array
    {
        return $this->list('/loading-lists');
    }

    public function loadingList(string $id): array
    {
        return $this->one("/loading-lists/{$id}");
    }

    public function containers(): array
    {
        return $this->list('/containers');
    }

    public function container(string $containerNumber): array
    {
        return $this->one("/containers/{$containerNumber}");
    }

    /**
     * Probe Vinstack for container tracking. Returns null if endpoint is missing (404).
     *
     * Tried paths (documented for ops): GET /containers/{id}/tracking, /track, tracking on detail.
     *
     * @return array<string, mixed>|null
     */
    public function containerTracking(string $containerNumber): ?array
    {
        $encoded = rawurlencode($containerNumber);

        foreach (["/containers/{$encoded}/tracking", "/containers/{$encoded}/track"] as $path) {
            try {
                $data = $this->one($path);

                if ($data !== []) {
                    return $data;
                }
            } catch (RuntimeException $e) {
                if (! str_contains($e->getMessage(), '(404)')) {
                    throw $e;
                }
            }
        }

        try {
            $detail = $this->container($containerNumber);
        } catch (RuntimeException) {
            return null;
        }

        foreach (['tracking', 'shipment_tracking', 'container_tracking'] as $key) {
            $nested = $detail[$key] ?? null;

            if (is_array($nested) && $nested !== []) {
                return $nested;
            }
        }

        return null;
    }

    public function payments(): array
    {
        return $this->list('/payments');
    }

    public function payment(string $id): array
    {
        return $this->one("/payments/{$id}");
    }

    public function invoices(): array
    {
        return $this->list('/invoices');
    }

    public function invoice(string $id): array
    {
        return $this->one("/invoices/{$id}");
    }

    public function parts(): array
    {
        return $this->list('/parts');
    }

    public function part(string $id): array
    {
        return $this->one("/parts/{$id}");
    }

    public function quotes(): array
    {
        return $this->list('/quotes');
    }

    public function quote(string $id): array
    {
        return $this->one("/quotes/{$id}");
    }

    protected function list(string $path): array
    {
        return $this->extractList($this->request('get', $path));
    }

    protected function one(string $path): array
    {
        $json = $this->request('get', $path);

        if (isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }

        return is_array($json) ? $json : [];
    }

    protected function request(string $method, string $path): array
    {
        /** @var Response $response */
        $response = $this->client()->{$method}($path);

        if ($response->failed()) {
            $message = $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            throw new RuntimeException(
                "Vinstack API error ({$response->status()}): {$message}"
            );
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    protected function extractList(array $json): array
    {
        $data = $json['data'] ?? $json;

        if (! is_array($data)) {
            return [];
        }

        return array_values(array_filter($data, fn ($item) => is_array($item)));
    }

    protected function client(): PendingRequest
    {
        $settings = VinstackSetting::current();

        $baseUrl = $settings->api_base_url
            ?: config('services.vinstack.base_url')
            ?: self::DEFAULT_BASE_URL;

        $token = $settings->api_token ?: config('services.vinstack.token');

        if (! $token) {
            throw new RuntimeException('Vinstack API token is not configured.');
        }

        $token = trim($token);
        if (! str_starts_with($token, 'vk_')) {
            $token = 'vk_'.$token;
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($token)
            ->acceptJson()
            ->timeout(60);
    }
}
