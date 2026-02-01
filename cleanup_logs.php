<?php
// ADJUST PATHS FOR SERVER ENVIRONMENT (Running from /var/www/isotank-system/api)
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- CLEANING UP LOG DATA (SERVER) ---\n";

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
        echo "Truncating $table...\n";
        DB::table($table)->truncate();
    }
}

echo "Resetting Master Isotank Status...\n";
DB::table('master_isotanks')->update([
    'status' => 'active', 
    'filling_status_code' => null, 
    'filling_status_desc' => null
]);

DB::statement('SET FOREIGN_KEY_CHECKS = 1');

echo "--- CLEANUP COMPLETE ---\n";
