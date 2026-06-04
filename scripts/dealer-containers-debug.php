<?php

/**
 * Manual check: dealer container list vs assigned vehicle raw_data.
 *
 * Usage (from project root):
 *   php scripts/dealer-containers-debug.php [dealer_id]
 */

use App\Models\Dealer;
use App\Services\ContainerService;
use App\Services\VinstackService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Arr;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dealerId = (int) ($argv[1] ?? 0);

if ($dealerId < 1) {
    fwrite(STDERR, "Usage: php scripts/dealer-containers-debug.php <dealer_id>\n");
    exit(1);
}

$dealer = Dealer::query()->with(['activeAssignments.vehicle'])->find($dealerId);

if (! $dealer) {
    fwrite(STDERR, "Dealer #{$dealerId} not found.\n");
    exit(1);
}

$containers = app(ContainerService::class)->listForDealer($dealer);

echo "Dealer #{$dealer->id} ({$dealer->company_name})\n";
echo 'Listed containers: '.count($containers)."\n\n";

foreach ($dealer->activeAssignments as $assignment) {
    $vehicle = $assignment->vehicle;
    $raw = is_array($vehicle->raw_data) ? $vehicle->raw_data : [];

    echo "Vehicle #{$vehicle->id} VIN=".($vehicle->vin ?: '—')."\n";
    echo '  raw container_number: '.(Arr::get($raw, 'container_number') ?: '—')."\n";
    echo '  raw booking_number: '.(Arr::get($raw, 'booking_number') ?: '—')."\n";
}

echo "\n--- API containers (admin list) ---\n";

try {
    $api = app(VinstackService::class)->containers();
    echo 'Vinstack count: '.count($api)."\n";

    foreach (array_slice($api, 0, 5) as $row) {
        if (! is_array($row)) {
            continue;
        }

        $autos = $row['autos'] ?? $row['vehicles'] ?? [];
        $vins = is_array($autos)
            ? array_slice(array_map(fn ($a) => is_array($a) ? ($a['vin'] ?? '') : '', $autos), 0, 3)
            : [];

        echo '  '.($row['container_number'] ?? '?')
            .' booking='.($row['booking_number'] ?? '—')
            .' autos='.implode(',', array_filter($vins))."\n";
    }
} catch (Throwable $e) {
    echo 'Vinstack error: '.$e->getMessage()."\n";
}

echo "\n--- Dealer list (merged) ---\n";

foreach ($containers as $row) {
    echo '  ['.($row['source'] ?? '?').'] '
        .($row['container_number'] ?? '—')
        .' booking='.($row['booking_number'] ?? '—')
        .' vehicles='.count($row['vehicles'] ?? [])."\n";
}
