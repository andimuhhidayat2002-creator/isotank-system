<?php
require __DIR__.'/api/vendor/autoload.php';
$app = require_once __DIR__.'/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionItem;

echo "--- CHECKING INSPECTION ITEMS CODES ---\n";
$items = InspectionItem::where('label', 'like', '%Port Suction%')->first(); // Use first() to avoid loop if possible or just get()

if($items) {
    echo "ID: " . $items->id . "\n";
    echo "Label: " . $items->label . "\n";
    echo "Code: " . $items->code . "\n";
} else {
    echo "Item not found.\n";
}
