<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterIsotank;
use Illuminate\Support\Facades\DB;

class DebugMaintenanceData extends Command
{
    protected $signature = 'debug:maintenance-data';
    protected $description = 'Debug maintenance data upload issues';

    public function handle()
    {
        $this->info('Starting Debug Process...');

        // 1. Check Isotank Sample
        $sampleIsos = ['KYNU241186-5', 'SIMU810997-8', 'JSDU180029-9'];
        foreach ($sampleIsos as $iso) {
            $this->info("Checking ISO: $iso");
            
            $exact = MasterIsotank::where('iso_number', $iso)->first();
            if ($exact) {
                $this->info("  [FOUND-EXACT] ID: {$exact->id}, Status: {$exact->status}");
            } else {
                $this->error("  [NOT-FOUND-EXACT]");
                
                // Try case insensitive
                $like = MasterIsotank::where('iso_number', 'LIKE', $iso)->first();
                if ($like) {
                    $this->info("  [FOUND-LIKE] ID: {$like->id}, Real ISO: {$like->iso_number}");
                } else {
                    $this->error("  [NOT-FOUND-LIKE]");
                }
            }
        }

        // 2. Check Table Status
        $count = DB::table('maintenance_jobs')->count();
        $this->info("Total Maintenance Jobs: $count");

        // 3. Check Recent Uploads
        $recent = DB::table('maintenance_jobs')->orderBy('created_at', 'desc')->limit(5)->get();
        if ($recent->isEmpty()) {
            $this->warn("No maintenance jobs found.");
        } else {
            foreach ($recent as $job) {
                $this->info("  Job ID: {$job->id}, Planned: {$job->planned_date}, Created: {$job->created_at}");
            }
        }
    }
}
