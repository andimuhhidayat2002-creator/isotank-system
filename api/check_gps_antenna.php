<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$item = App\Models\InspectionItem::where('code', 'gps_antenna')->first();
if ($item) {
    echo "Code: " . $item->code . "\n";
    echo "Applies to: " . json_encode($item->applicable_categories) . "\n";
    echo "Is active: " . ($item->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "Item gps_antenna not found";
}
