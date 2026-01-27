<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$logs = App\Models\InspectionLog::where('inspection_data', 'like', '%antenna%')
    ->orWhere('inspection_data', 'like', '%regulator%')
    ->take(5)
    ->get();

foreach($logs as $log) {
    echo "Log ID: " . $log->id . "\n";
    $data = $log->inspection_data;
    if (is_string($data)) $data = json_decode($data, true);
    foreach($data as $k => $v) {
        if (str_contains(strtolower($k), 'antenna') || str_contains(strtolower($k), 'regulator')) {
            echo "  $k => $v\n";
        }
    }
}
