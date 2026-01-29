<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    DB::beginTransaction();
    
    DB::statement('SET FOREIGN_KEY_CHECKS = 0');

    $tables = [
        'receiver_confirmations',
        'inspection_logs',
        'inspection_jobs',
        'maintenance_jobs',
        'vacuum_logs',
        'vacuum_suction_activities',
        'calibration_logs',
        'master_latest_inspections',
        'master_isotank_item_status',
        'master_isotank_measurement_status',
        'master_isotank_calibration_status',
        'excel_upload_logs',
        'activity_uploads',
        'isotank_uploads'
    ];

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "Truncated: $table\n";
        }
    }

    // Reset master_isotanks
    DB::table('master_isotanks')->update([
        'status' => 'active',
        'filling_status_code' => null,
        'filling_status_desc' => null
    ]);
    echo "Reset master_isotanks status and filling info.\n";

    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    
    DB::commit();
    echo "Reset Completed Successfully!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during reset: " . $e->getMessage() . "\n";
}
