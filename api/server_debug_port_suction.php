<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterLatestInspection;
use App\Models\InspectionLog;

echo "--- DEBUG LATEST INSPECTIONS (Server Verification) ---\n";

$latests = MasterLatestInspection::with('lastInspectionLog', 'isotank')
    ->orderBy('updated_at', 'desc')
    ->limit(5)
    ->get();

foreach ($latests as $rec) {
    echo "\nISO: " . ($rec->isotank->iso_number ?? 'Unknown') . "\n";
    echo "Master Col [vacuum_port_suction_condition]: " . ($rec->vacuum_port_suction_condition ?? 'NULL') . "\n";
    
    if ($rec->lastInspectionLog) {
        echo "Log ID: " . $rec->lastInspectionLog->id . "\n";
        $data = $rec->lastInspectionLog->inspection_data;
        if (is_string($data)) $data = json_decode($data, true);
        
        $keys = ['port_suction_condition', 'vacuum_port_suction_condition', 'Port Suction Condition', 'Port_Suction_Condition'];
        $found = false;
        foreach($keys as $k) {
            if (isset($data[$k])) {
                echo "JSON Key ['$k']: " . $data[$k] . "\n";
                $found = true;
            }
        }
        if (!$found) echo "JSON: No port suction keys found.\n";
        
        // Dump all keys containing 'suction' or 'vacuum' to be sure
        $related = [];
        foreach($data as $k => $v) {
            if (stripos($k, 'suction') !== false || stripos($k, 'vacuum') !== false) {
                // Skip common vacuum values to keep output clean
                if (in_array($k, ['vacuum_value', 'vacuum_unit', 'vacuum_temperature', 'vacuum_check_datetime'])) continue;
                 $related[] = "$k=$v";
            }
        }
        echo "Related Keys: " . implode(', ', $related) . "\n";
        
    } else {
        echo "Linked Log: NULL\n";
    }
}
