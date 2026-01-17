<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = App\Models\InspectionLog::whereNull('filling_status_desc')->with('isotank')->get();
$count = 0;
foreach($logs as $log) {
    if($log->isotank) {
        $log->update([
            'filling_status_code' => $log->isotank->filling_status_code,
            'filling_status_desc' => $log->isotank->filling_status_desc,
        ]);
        $count++;
    }
}
echo "Updated $count logs.";
