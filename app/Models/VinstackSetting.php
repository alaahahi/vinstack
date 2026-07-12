<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VinstackSetting extends Model
{
    protected $fillable = [
        'api_base_url',
        'api_token',
        'gallery_api_base_url',
        'gallery_api_token',
        'gallery_token_expired',
        'gallery_token_checked_at',
        'sync_enabled',
        'last_sync_at',
        'last_auto_sync_at',
        'support_phone',
        'vehicle_options',
        'cloudinary_cloud_name',
        'cloudinary_api_key',
        'cloudinary_api_secret',
        'cloudinary_upload_preset',
        'cloudinary_folder',
        'image_transfer_async_enabled',
        'image_transfer_batch_size',
        'wa_queue_base_url',
        'wa_queue_sender_id',
        'wa_queue_enabled',
        'dealer_notification_events',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'gallery_api_token' => 'encrypted',
            'cloudinary_api_secret' => 'encrypted',
            'gallery_token_expired' => 'boolean',
            'gallery_token_checked_at' => 'datetime',
            'sync_enabled' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_auto_sync_at' => 'datetime',
            'vehicle_options' => 'array',
            'wa_queue_enabled' => 'boolean',
            'image_transfer_async_enabled' => 'boolean',
            'dealer_notification_events' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'api_base_url' => config('services.vinstack.base_url'),
            'gallery_api_base_url' => config('services.vinstack.gallery_base_url'),
            'sync_enabled' => true,
        ]);
    }
}
