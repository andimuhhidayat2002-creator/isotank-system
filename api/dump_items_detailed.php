<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionItem;

$items = InspectionItem::where('label', 'LIKE', '%GPS%')
    ->orWhere('label', 'LIKE', '%Antenna%')
    ->get();

foreach ($items as $item) {
    echo "ID: " . $item->id . "\n";
    echo "Code: " . $item->code . "\n";
    echo "Label: " . $item->label . "\n";
    echo "Category: " . $item->category . "\n";
    echo "Active: " . $item->is_active . "\n";
    echo "Order: " . $item->order . "\n";
    echo "Applicable: " . json_encode($item->applicable_categories) . "\n";
    echo "---------------------------\n";
}
