<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VacuumLog;
use App\Models\MasterIsotankMeasurementStatus;
use Illuminate\Support\Facades\DB;

class SyncVacuumStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isotank:sync-vacuum-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the latest vacuum log reading to the Master Measurement Status table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Vacuum Status Sync...");

        // Get latest vacuum log ID for each isotank
        // We use a subquery approach to get the latest log per isotank
        $latestLogIds = VacuumLog::select(DB::raw('MAX(id) as id'))
            ->groupBy('isotank_id')
            ->pluck('id');

        $count = $latestLogIds->count();
        $this->info("Found {$count} unique isotanks with vacuum logs.");

        if ($count === 0) {
            $this->info("No logs to sync.");
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Process in chunks to manage memory
        // We iterate specifically over the IDs we identified as the latest
        VacuumLog::whereIn('id', $latestLogIds)->chunk(100, function ($logs) use ($bar) {
            foreach ($logs as $log) {
                // Update Master Status
                // We use updateOrCreate to ensure we handle both existing and new status records
                MasterIsotankMeasurementStatus::updateOrCreate(
                    ['isotank_id' => $log->isotank_id],
                    [
                        'vacuum_mtorr' => $log->vacuum_value_mtorr,
                        'temperature' => $log->temperature,
                        'last_measurement_at' => $log->check_datetime,
                        // We do not overwrite pressure/level as they come from different sources/inspections
                        // updateOrCreate will merge these values into existing record
                    ]
                );
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Sync completed successfully.");
    }
}
