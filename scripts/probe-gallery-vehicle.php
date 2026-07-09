<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$vin = $argv[1] ?? '1FMEE7BH2SLB34198';
$vehicle = App\Models\Vehicle::query()->where('vin', $vin)->first();

if (! $vehicle) {
    echo "Vehicle not found for VIN: {$vin}\n";
    exit(1);
}

$gallery = app(App\Services\VinstackGalleryService::class);
$ids = $gallery->resolveGalleryIdentifiers($vehicle);

echo "Vehicle #{$vehicle->id} source={$vehicle->source->value} ids=".json_encode($ids)."\n";

foreach ($ids as $id) {
    echo "\n--- Trying gallery fetch for: {$id} ---\n";
    try {
        $result = $gallery->fetchGallery($id);
        echo "OK keys: ".implode(', ', array_keys($result))."\n";
    } catch (Throwable $e) {
        echo "ERROR: ".$e->getMessage()."\n";
    }
}
