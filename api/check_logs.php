<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = App\Models\InspectionLog::with('isotank')
    ->whereHas('isotank', function($q) {
        $q->where('iso_number', 'KYNUTES');
    })
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($logs as $log) {
    echo "ID: " . $log->id . " | Date: " . $log->inspection_date->format('Y-m-d') . "\n";
    echo "Filling Status Code: " . ($log->filling_status_code ?? 'NULL') . "\n";
    echo "Filling Status Desc: " . ($log->filling_status_desc ?? 'NULL') . "\n";
    echo "Isotank Filling Status: " . $log->isotank->filling_status_code . "\n";
    echo "---------------------------\n";
}
