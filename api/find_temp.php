<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\InspectionItem::all() as $item) {
    if (stripos($item->label, 'Temp') !== false) {
        echo "ID: " . $item->id . " | Label: " . $item->label . " | Category: " . $item->category . " | Code: " . $item->code . " | Category JSON: " . json_encode($item->applicable_categories) . "\n";
    }
}
