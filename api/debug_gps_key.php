<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionLog;
use App\Models\InspectionItem;

// Check items for ID 84 again strictly
$item84 = InspectionItem::find(84);
echo "Item 84 Code: " . $item84->code . "\n";
echo "Item 84 Label: " . $item84->label . "\n";

// Get latest T75 log
$log = InspectionLog::whereHas('isotank', function($q) {
    $q->where('tank_category', 'T75')->orWhereNull('tank_category');
})->orderByDesc('created_at')->first();

if ($log) {
    echo "Log ID: " . $log->id . "\n";
    echo "Isotank: " . $log->isotank->iso_number . "\n";
    echo "Category: " . $log->isotank->tank_category . "\n";
    
    $data = $log->inspection_data;
    if (is_string($data)) $data = json_decode($data, true);
    
    echo "--- KEYS IN DATA ---\n";
    foreach ($data as $k => $v) {
        if (strpos($k, 'gps') !== false || strpos($k, 'antenna') !== false) {
            echo "Match found in data: [$k] => $v\n";
        }
    }
    
    // Check if 'gps_antenna' exists
    if (array_key_exists('gps_antenna', $data)) {
        echo "Direct 'gps_antenna' key EXISTS. Value: " . $data['gps_antenna'] . "\n";
    } else {
        echo "Direct 'gps_antenna' key MISSING.\n";
    }
} else {
    echo "No T75 logs found.\n";
}
