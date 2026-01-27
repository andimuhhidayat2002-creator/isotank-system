<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$log = App\Models\InspectionLog::latest()->first();

if ($log) {
    echo "Log ID: " . $log->id . " | Isotank ID: " . $log->isotank_id . "\n";
    echo "Inspection Data Keys:\n";
    $data = $log->inspection_data;
    if (is_string($data)) $data = json_decode($data, true);
    print_r(array_keys($data));
    echo "\nValues for suspicious keys:\n";
    foreach(['gps_antenna', 'esdv_regulator', 'antenna', 'regulator'] as $k) {
        if (isset($data[$k])) echo "$k => " . $data[$k] . "\n";
    }
} else {
    echo "No log found";
}
