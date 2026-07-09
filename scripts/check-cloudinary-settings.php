<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cloudinary = app(App\Services\CloudinaryService::class);

echo json_encode([
    'cloudinary_configured' => $cloudinary->isConfigured(),
    'probe' => $cloudinary->probe(false),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
