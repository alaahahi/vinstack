<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\VinstackSetting::current();
$gallery = app(App\Services\VinstackGalleryService::class);
$creds = $gallery->resolveCredentials();
$probe = $gallery->probeSettings();

echo json_encode([
    'settings' => $settings->only([
        'gallery_api_base_url',
        'gallery_token_expired',
        'api_token_set' => (bool) $settings->api_token,
        'gallery_api_token_set' => (bool) $settings->gallery_api_token,
    ]),
    'credentials' => [
        'base_url' => $creds['base_url'],
        'token_source' => $creds['token_source'],
        'has_token' => $creds['token'] !== '',
    ],
    'probe' => $probe,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
