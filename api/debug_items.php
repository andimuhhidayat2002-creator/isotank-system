<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionItem;

echo "Checking Inspection Items for T75...\n";

$items = InspectionItem::where('is_active', true)->orderBy('order')->get();

foreach ($items as $item) {
    echo "ID: {$item->id} | Code: {$item->code} | Cats: " . json_encode($item->applicable_categories) . "\n";
}
