<?php
// Debug script to check InspectionLog data structure
require __DIR__.'/api/vendor/autoload.php';
$app = require_once __DIR__.'/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionLog;

// Get latest T75 inspection log
$log = InspectionLog::whereHas('isotank', function($q) {
    $q->where('tank_category', 'T75');
})->latest()->first();

if (!$log) {
    echo "No T75 logs found.\n";
    exit;
}

echo "Log ID: " . $log->id . "\n";
echo "Inspector: " . $log->inspector_name . "\n";
echo "Date: " . $log->inspection_date . "\n";
echo "\n--- COLUMNS ---\n";
echo "vacuum_gauge_condition: " . $log->vacuum_gauge_condition . "\n";
echo "vacuum_port_suction_condition: " . $log->vacuum_port_suction_condition . "\n"; // Check if this column exists/has data

echo "\n--- JSON DATA (First 50 keys) ---\n";
$data = $log->inspection_data;
if (is_string($data)) $data = json_decode($data, true);

foreach ($data as $k => $v) {
    echo "[$k] => " . (is_string($v) || is_numeric($v) ? $v : json_encode($v)) . "\n";
}

echo "\n--- SPECIFIC SEARCH ---\n";
$searchInfo = [
    'port', 'suction', 'vacuum'
];

foreach ($data as $k => $v) {
    foreach ($searchInfo as $term) {
        if (stripos($k, $term) !== false) {
            echo "MATCH '$term': [$k] => $v\n";
        }
    }
}
