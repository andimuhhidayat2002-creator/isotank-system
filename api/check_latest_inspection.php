<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InspectionLog;

$log = InspectionLog::with('isotank')->latest()->first();
if ($log) {
    echo "Latest Inspection Log:\n";
    echo "ID: {$log->id}\n";
    echo "Isotank: {$log->isotank->iso_number}\n";
    echo "Category: {$log->isotank->tank_category}\n";
    echo "Type: {$log->inspection_type}\n";
    echo "Date: {$log->inspection_date}\n\n";
    
    $data = json_decode($log->inspection_data, true) ?? [];
    echo "Inspection Data Keys:\n";
    foreach ($data as $k => $v) {
        if (str_contains($k, 'GPS') || str_contains($k, 'T11_E') || str_contains($k, 'Antena')) {
            echo "  - $k => $v\n";
        }
    }
    
    if (isset($data['T11_E_09'])) {
        echo "\n✅ T11_E_09 (Antena,GPS,4G) found: {$data['T11_E_09']}\n";
    } else {
        echo "\n❌ T11_E_09 (Antena,GPS,4G) NOT found in inspection_data\n";
    }
} else {
    echo "No inspection logs found.\n";
}
