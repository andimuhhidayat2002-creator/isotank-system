<?php

use Illuminate\Support\Facades\DB;
use App\Models\MaintenanceJob;
use App\Models\ActivityUpload;

try {
    DB::beginTransaction();

    // 1. Get count before delete
    $count = MaintenanceJob::count();

    // 2. Clear Maintenance Jobs
    // If there ARE foreign keys, TRUNCATE might fail without this
    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    DB::table('maintenance_jobs')->truncate();
    echo "Successfully truncated maintenance_jobs (Deleted $count records).\n";

    // 3. Optional: Clear Activity Upload logs (history of the excel upload)
    $uploadCount = ActivityUpload::count();
    DB::table('activity_uploads')->truncate();
    echo "Successfully truncated activity_uploads (Deleted $uploadCount upload logs).\n";

    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    DB::commit();
    echo "\nMaintenance data has been cleared. You can now re-upload your fixed Excel file.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
