<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetOperationalData extends Command
{
    protected $signature = 'app:reset-operational-data {--force : Force the operation to run when in production}';

    protected $description = 'Truncate all operational data tables (Isotanks, Logs, Jobs) but keep Users and Config.';

    public function handle()
    {
        if (!$this->option('force')) {
            $this->warn("CAUTION: This will delete ALL isotanks and history.");
            $this->warn("SAFE TABLES (Will NOT be deleted): Users, Roles, Inspection Items, Config.");
            if (!$this->confirm('Are you sure you want to delete ALL operational data?')) {
                return;
            }
        }

        $this->info('Starting data cleanup...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // TABLES TO TRUNCATE (Operational Data ONLY)
        $tables = [
            'master_isotanks',                  // The Isotanks themselves
            'inspection_jobs',                  // Pending Inspections
            'inspection_logs',                  // Completed Inspection Reports
            'maintenance_jobs',                 // Maintenance History
            'calibration_logs',                 // Calibration History
            'vacuum_logs',                      // Vacuum History
            'activity_uploads',                 // Upload Logs
            'isotank_uploads',                  // Master Upload Logs
            'class_surveys',                    // CSC/Class Data
            'master_isotank_components',        // Attached Components (Valves etc)
            'master_isotank_calibration_statuses', // Current Calibration State
            'master_isotank_measurement_statuses', // Current Measurement State
            'master_latest_inspections',           // Latest Inspection Snapshot (IMPORTANT)
            'vacuum_suction_activities',           // Vacuum Process Logs
        ];

        // EXPLICITLY NOT INCLUDED (SAFE):
        // - users
        // - roles
        // - permissions
        // - inspection_items (The checklist definitions)
        // - migrations

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("Truncated: $table");
            } catch (\Exception $e) {
                $this->warn("Skipped/Error $table: " . $e->getMessage());
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Database cleaned.');

        $this->info('Cleaning uploaded files...');
        
        $folders = [
            'activity-uploads',
            'isotank-uploads',
            'inspection-pdfs',
        ];

        foreach ($folders as $folder) {
            Storage::deleteDirectory($folder);
            Storage::makeDirectory($folder); 
            $this->line("Cleaned folder: $folder");
        }

        $this->info('All operational data has been successfully reset. Configuration preserved.');
    }
}
