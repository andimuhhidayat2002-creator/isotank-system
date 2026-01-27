<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$isotankId = 865;
$log = App\Models\InspectionLog::where('isotank_id', $isotankId)->latest()->first();

if ($log) {
    echo "Log ID: " . $log->id . "\n";
    echo "Inspection Data Keys:\n";
    $data = $log->inspection_data;
    if (is_string($data)) $data = json_decode($data, true);
    print_r(array_keys($data));
} else {
    echo "No log found for Isotank $isotankId";
}
