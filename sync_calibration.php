<?php

use App\Models\MasterIsotankComponent;
use App\Models\MasterIsotankCalibrationStatus;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();
    
    $components = MasterIsotankComponent::where('is_active', true)->get();
    $count = 0;

    foreach ($components as $comp) {
        $itemName = null;
        $type = strtoupper($comp->component_type);
        
        if ($type === 'PG') {
            $itemName = 'pressure_gauge';
        } elseif ($type === 'PSV') {
            $pos = $comp->position_code;
            // Map common position names to 1-4 if needed, otherwise use as is
            if (empty($pos)) $pos = '1';
            $itemName = 'psv' . $pos;
        }

        if ($itemName) {
            $status = 'valid';
            if ($comp->expiry_date && \Carbon\Carbon::parse($comp->expiry_date)->isPast()) {
                $status = 'expired';
            }

            MasterIsotankCalibrationStatus::updateOrCreate(
                [
                    'isotank_id' => $comp->isotank_id,
                    'item_name' => $itemName,
                ],
                [
                    'serial_number' => $comp->serial_number,
                    'calibration_date' => $comp->last_calibration_date,
                    'valid_until' => $comp->expiry_date,
                    'status' => $status,
                ]
            );
            $count++;
        }
    }

    DB::commit();
    echo "Successfully synced $count calibration status records from components table.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during sync: " . $e->getMessage() . "\n";
}
