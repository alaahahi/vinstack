<?php

use App\Services\PortGeocoderService;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$geocoder = $app->make(PortGeocoderService::class);

$cases = [
    'Toronto',
    'Toronto, Canada',
    'Mersin',
    'Mersin, Turkey',
    'TORONTO PORT',
    'Wilmington, US',
    'Gebze, Turkey',
];

echo "Port geocoder v3 tests\n";
echo str_repeat('=', 60)."\n";

foreach ($cases as $label) {
    Cache::forget('port_geocode:v3:'.md5(mb_strtolower($label)));
    $result = $geocoder->resolve($label);

    echo "\nLabel: {$label}\n";

    if ($result === null) {
        echo "  => NULL\n";

        continue;
    }

    echo '  name: '.($result['name'] ?? '—')."\n";
    echo '  lat: '.($result['lat'] ?? '—').', lng: '.($result['lng'] ?? '—')."\n";
    echo '  geocoded: '.($result['geocoded'] ? 'yes' : 'no')."\n";
    echo '  confidence: '.($result['geocode_confidence'] ?? '—')."\n";
    echo '  provider: '.($result['geocode_provider'] ?? '—')."\n";
}

echo "\n".str_repeat('=', 60)."\nDone.\n";
