<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InspectionLog;
use App\Models\MasterLatestInspection;

echo "--- PATCHING INSPECTION LOGS FROM MASTER ---\n";
// Find logs with empty vacuum_port_suction_condition
$logs = InspectionLog::whereNull('vacuum_port_suction_condition')
    ->orWhere('vacuum_port_suction_condition', '')
    ->get();

foreach ($logs as $log) {
    echo "Log ID: " . $log->id . " (ISO: " . ($log->isotank->iso_number ?? '?') . ")\n";
    
    // Check if this log is the "latest" one linked to Master
    $master = MasterLatestInspection::where('isotank_id', $log->isotank_id)
        ->where('inspection_log_id', $log->id)
        ->first();
        
    if ($master && !empty($master->vacuum_port_suction_condition)) {
        echo "  Found in Master: " . $master->vacuum_port_suction_condition . ". PATCHING...\n";
        $log->vacuum_port_suction_condition = $master->vacuum_port_suction_condition;
        $log->save();
        echo "  Saved.\n";
    } else {
        echo "  Not found in Master (or not linked as latest).\n";
        
        // Alternative: Check Master Item Status
        $status = \App\Models\MasterIsotankItemStatus::where('isotank_id', $log->isotank_id)
            ->where('item_name', 'vacuum_port_suction_condition') // Item name
            ->first();
            
        if (!$status) {
             // Try dynamic name
             $status = \App\Models\MasterIsotankItemStatus::where('isotank_id', $log->isotank_id)
            ->where('item_name', 'port_suction_condition') 
            ->first();
        }

        if ($status && !empty($status->condition)) {
             echo "  Found in Item Status: " . $status->condition . ". PATCHING (Assumption: Valid for this log)...\n";
             $log->vacuum_port_suction_condition = $status->condition;
             $log->save();
             echo "  Saved.\n";
        }
    }
}
echo "--- PATCH COMPLETE ---\n";
