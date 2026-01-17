<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterIsotankComponent;

class RecalculateCalibrationExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calibration:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate empty expiry dates based on last calibration date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $components = MasterIsotankComponent::whereNotNull('last_calibration_date')
            ->whereNull('expiry_date')
            ->get();

        $count = 0;
        foreach ($components as $comp) {
            $calDate = $comp->last_calibration_date;
            $updated = false;

            if ($comp->component_type === 'PG') {
                $comp->expiry_date = $calDate->copy()->addMonths(6);
                $updated = true;
            } elseif ($comp->component_type === 'PSV') {
                $comp->expiry_date = $calDate->copy()->addYear();
                $updated = true;
            }

            if ($updated) {
                $comp->save();
                $count++;
            }
        }

        $this->info("Updated {$count} components.");

        $this->info("Syncing Master Calibration Statuses...");
        
        \App\Models\MasterIsotank::with('components')->chunk(100, function ($tanks) {
            foreach ($tanks as $tank) {
                // Find earliest expiry of active components
                $earliest = $tank->components->where('is_active', true)
                                           ->whereNotNull('expiry_date')
                                           ->min('expiry_date');
                
                // Determine status
                $status = 'valid'; // Default to valid to match ENUM
                if ($earliest) {
                    $earliestDate = \Carbon\Carbon::parse($earliest);
                    $status = $earliestDate->isPast() ? 'expired' : 'valid';
                }

                \App\Models\MasterIsotankCalibrationStatus::updateOrCreate(
                    [
                        'isotank_id' => $tank->id,
                        'item_name' => 'General' // Unique key
                    ],
                    [
                        'last_calibration_date' => null, 
                        'valid_until' => $earliest,
                        'status' => $status,
                        'certificate_number' => null,
                        'serial_number' => null   
                    ]
                );
            }
        });
        
        $this->info("Sync Complete.");
    }
}
