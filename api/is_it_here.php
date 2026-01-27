<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\InspectionItem::all() as $item) {
    if ($item->label == 'Temperatur') {
        echo "FOUND! ID: " . $item->id . " | Cat: " . $item->category . " | Codes: " . json_encode($item->applicable_categories) . "\n";
    }
}
