<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vehicle;

$byStatus = Vehicle::query()
    ->selectRaw('status, source, count(*) as c')
    ->groupBy('status', 'source')
    ->get();

echo "Grouped by status/source:\n";
foreach ($byStatus as $row) {
    $status = $row->status?->value ?? 'null';
    $source = $row->source?->value ?? 'null';
    echo "  status={$status} source={$source} count={$row->c}\n";
}

echo "\nDefault list count (no filters): " . Vehicle::query()->count() . "\n";
