<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Inspection Logs Count: " . DB::table('inspection_logs')->count() . "\n";
echo "Master Calibration Count: " . DB::table('master_isotank_calibration_status')->count() . "\n";

// Show sample data
echo "\nSample Inspection Logs (latest 3):\n";
$logs = DB::table('inspection_logs')
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get(['id', 'isotank_id', 'inspection_date', 'is_draft']);
foreach ($logs as $log) {
    echo "- ID: {$log->id}, Isotank: {$log->isotank_id}, Date: {$log->inspection_date}, Draft: " . ($log->is_draft ? 'Yes' : 'No') . "\n";
}

echo "\nSample Master Calibration (latest 5):\n";
$cals = DB::table('master_isotank_calibration_status')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['isotank_id', 'item_name', 'serial_number', 'calibration_date']);
foreach ($cals as $cal) {
    echo "- Isotank: {$cal->isotank_id}, Item: {$cal->item_name}, Serial: {$cal->serial_number}, Cal Date: {$cal->calibration_date}\n";
}
