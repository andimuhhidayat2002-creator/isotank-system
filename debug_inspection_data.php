<?php
// Load Laravel App
require __DIR__ . '/api/vendor/autoload.php';
$app = require_once __DIR__ . '/api/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\InspectionLog;

// Get latest T75 inspection
$log = InspectionLog::whereHas('isotank', function($q){
    $q->where('tank_category', 'T75');
})->latest()->first();

if(!$log) {
    echo "No T75 Inspection found.\n";
    exit;
}

echo "Inspection ID: " . $log->id . "\n";
echo "Type: " . $log->inspection_type . "\n";
echo "Date: " . $log->inspection_date . "\n";
echo "\n--- RAW INSPECTION DATA (JSON) ---\n";
// Decode to array
$data = is_string($log->inspection_data) ? json_decode($log->inspection_data, true) : $log->inspection_data;
print_r($data);

echo "\n--- CHECKING KEYS ---\n";
$keysToCheck = ['vacuum_port_suction_condition', 'port_suction_condition', 'Port Suction Condition', 'port_condition'];
foreach($keysToCheck as $k) {
    echo "'$k' => " . ($data[$k] ?? 'NULL') . "\n";
}
