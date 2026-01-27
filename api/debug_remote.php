<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- CHECKING ITEMS ---\n";
$items = \App\Models\InspectionItem::where('label', 'LIKE', '%GPS%')->get();
foreach($items as $i) {
    echo "ID: {$i->id} | Code: {$i->code} | Label: {$i->label} | Cats: " . (is_string($i->applicable_categories) ? $i->applicable_categories : json_encode($i->applicable_categories)) . "\n";
}

echo "\n--- CHECKING LOG 31 ---\n";
$log = \App\Models\InspectionLog::find(31);
if($log) {
    echo "Log 31 found. Isotank: {$log->isotank_id} (" . ($log->isotank?->tank_category ?? 'NULL') . ")\n";
    $data = $log->inspection_data;
    if(is_string($data)) $data = json_decode($data, true);
    
    if(is_array($data)) {
        echo "Data Keys Found: " . implode(", ", array_keys($data)) . "\n";
        // Check for specific suspicious keys
        foreach($data as $k => $v) {
             echo "Key: [$k] => Value: [$v]\n";
        }
    } else {
        echo "Data is not array.\n";
    }
} else {
    echo "Log 31 NOT FOUND.\n";
}
