<?php

namespace App\Services;

use App\Models\AccountingExportSetting;
use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\PhoneNormalizer;
use App\Support\VehicleGalleryStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CopartExportService
{
    /**
     * @return array{ok: bool, created: bool, queued: bool, car_id: int, import_id: int, client_id: int}
     */
    public function export(Vehicle $vehicle): array
    {
        $vehicle->loadMissing(['activeAssignment.dealer.user', 'uploadedImages']);

        $assignment = $vehicle->activeAssignment;

        if (! $assignment || ! $assignment->dealer) {
            throw new RuntimeException('يجب إسناد السيارة لتاجر قبل التصدير للمحاسبة.');
        }

        $dealer = $assignment->dealer;
        $user = $dealer->user;
        $phone = PhoneNormalizer::normalize($dealer->phone ?: $user?->phone);

        if (! $phone) {
            throw new RuntimeException('هاتف التاجر مطلوب لربط الحسابات. أضف هاتفاً للتاجر أولاً.');
        }

        $settings = AccountingExportSetting::current();
        $baseUrl = $settings->resolvedBaseUrl();
        $token = $settings->resolvedToken();

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('إعدادات تصدير المحاسبة غير مكتملة (URL / Token).');
        }

        if (! $settings->enabled) {
            throw new RuntimeException('تصدير المحاسبة غير مفعّل في الإعدادات.');
        }

        $payload = $this->buildPayload($vehicle, $dealer, $user, $phone);
        $url = $baseUrl.'/api/integration/vinstack/vehicles';

        $response = Http::timeout(90)
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            $message = $response->json('message')
                ?? ('Copart HTTP '.$response->status());

            Log::warning('copart export failed', [
                'vehicle_id' => $vehicle->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $vehicle->forceFill([
                'accounting_export_status' => 'failed',
                'accounting_export_error' => is_string($message) ? mb_substr($message, 0, 2000) : 'failed',
            ])->save();

            throw new RuntimeException(is_string($message) ? $message : 'فشل التصدير للمحاسبة.');
        }

        $data = $response->json('data') ?? [];
        $queued = (bool) ($data['queued'] ?? false);
        $carId = (int) ($data['car_id'] ?? 0);
        $importId = (int) ($data['import_id'] ?? 0);

        $vehicle->forceFill([
            'exported_to_accounting_at' => now(),
            'copart_car_id' => $carId > 0 ? $carId : ($importId > 0 ? $importId : null),
            'accounting_export_status' => $queued ? 'pending_approval' : 'exported',
            'accounting_export_error' => null,
        ])->save();

        return [
            'ok' => true,
            'created' => (bool) ($data['created'] ?? false),
            'queued' => $queued,
            'car_id' => $carId,
            'import_id' => $importId,
            'client_id' => (int) ($data['client_id'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(Vehicle $vehicle, Dealer $dealer, ?User $user, string $phone): array
    {
        $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

        $images = [];

        foreach ($vehicle->uploadedImages as $img) {
            $url = $img->publicUrl();
            if ($url !== '' && (str_starts_with($url, 'http') || str_starts_with($url, '/'))) {
                if (str_starts_with($url, '/')) {
                    $url = rtrim((string) config('app.url'), '/').$url;
                }
                $images[] = $url;
            }
        }

        if (VehicleGalleryStore::isEnabled()) {
            foreach (VehicleGalleryStore::formatted($vehicle) as $img) {
                $url = (string) ($img['url'] ?? '');
                if ($url !== '' && (str_starts_with($url, 'http') || str_starts_with($url, '/'))) {
                    if (str_starts_with($url, '/')) {
                        $url = rtrim((string) config('app.url'), '/').$url;
                    }
                    $images[] = $url;
                }
            }
        }

        if (is_array($vehicle->images)) {
            foreach ($vehicle->images as $url) {
                if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $images[] = $url;
                }
            }
        }

        foreach (['images', 'thumbnail_url'] as $key) {
            $val = $raw[$key] ?? null;
            if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL)) {
                $images[] = $val;
            }
            if (is_array($val)) {
                foreach ($val as $u) {
                    if (is_string($u) && filter_var($u, FILTER_VALIDATE_URL)) {
                        $images[] = $u;
                    }
                }
            }
        }

        $images = array_values(array_unique($images));

        return [
            'vin' => $vehicle->vin,
            'vinstack_vehicle_id' => $vehicle->id,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'price' => $vehicle->price,
            'color' => $raw['color'] ?? null,
            'lot' => $raw['lot'] ?? $raw['lot_number'] ?? null,
            'auction' => $raw['auction'] ?? $raw['auction_name'] ?? null,
            'purchase_date' => $raw['purchase_date'] ?? null,
            'loading_date' => $raw['loading_date'] ?? $raw['picked_up_date'] ?? null,
            'eta' => $vehicle->eta ?? $raw['eta'] ?? null,
            'loading_point' => $raw['loading_point'] ?? $raw['loading_port'] ?? null,
            'destination' => $raw['destination'] ?? $raw['destination_port'] ?? null,
            'booking_number' => $raw['booking_number'] ?? $raw['booking_id'] ?? null,
            'container_number' => $raw['container_number'] ?? $raw['container_id'] ?? null,
            'status' => is_object($vehicle->status) ? $vehicle->status->value : ($raw['status'] ?? null),
            'keys' => is_string($raw['keys'] ?? null) ? $raw['keys'] : null,
            'notes' => $vehicle->notes,
            'images' => $images,
            'raw_data' => $raw,
            'dealer' => [
                'phone' => $phone,
                'name' => $user?->name,
                'company_name' => $dealer->company_name,
                'email' => $user?->email,
            ],
        ];
    }
}
