<?php
require __DIR__.'/api/vendor/autoload.php';
$app = require_once __DIR__.'/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionItem;

echo "--- CHECKING INSPECTION ITEMS ---\n";
$items = InspectionItem::where('label', 'like', '%Port Suction%')->get();

foreach ($items as $item) {
    echo "ID: " . $item->id . "\n";
    echo "Label: " . $item->label . "\n";
    echo "Code: " . $item->code . "\n";
    echo "Category: " . $item->category . "\n";
    echo "-------------------\n";
}
