<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionLog;

echo "--- CHECKING INSPECTION LOG COLUMNS ---\n";
$logs = InspectionLog::orderBy('id', 'desc')->take(10)->get();

foreach ($logs as $log) {
    echo "Log ID: " . $log->id . "\n";
    echo "Date: " . $log->created_at . "\n";
    echo "Vacuum Port Suction (Col): [" . ($log->vacuum_port_suction_condition ?? 'NULL') . "]\n";
    echo "Data JSON keys: ";
    $data = is_string($log->inspection_data) ? json_decode($log->inspection_data, true) : $log->inspection_data;
    if (isset($data['port_suction_condition'])) echo "found 'port_suction_condition', ";
    if (isset($data['vacuum_port_suction_condition'])) echo "found 'vacuum_port_suction_condition', ";
    echo "\n-------------------\n";
}
