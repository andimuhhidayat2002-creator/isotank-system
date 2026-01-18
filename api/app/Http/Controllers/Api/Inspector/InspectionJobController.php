<?php

namespace App\Http\Controllers\Api\Inspector;

use App\Http\Controllers\Controller;
use App\Models\InspectionJob;
use App\Models\InspectionLog;
use App\Models\MasterIsotankItemStatus;
use App\Models\MasterIsotankMeasurementStatus;
use App\Models\MasterIsotankCalibrationStatus;
use App\Models\MasterIsotankComponent;
use App\Models\VacuumLog;
use Illuminate\Http\Request;

class InspectionJobController extends Controller
{
    /**
     * Get all open inspection jobs for inspector
     * 
     * RULES:
     * - Only show jobs with status = 'open'
     * - Only show jobs for ACTIVE isotanks
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = InspectionJob::with(['isotank'])
            ->where('status', 'open')
            ->whereHas('isotank', function ($q) {
                $q->where('status', 'active');
            });

        // Filter based on role
        if ($user->role === 'receiver') {
            // Receiver only sees outgoing jobs that have been submitted by inspector (not draft)
            $query->where('activity_type', 'outgoing_inspection')
                ->whereHas('inspectionLogs', function ($q) {
                    $q->where('is_draft', false);
                });
        } elseif ($user->role === 'inspector') {
            // Inspector should not see outgoing jobs that they have already submitted
            $query->where(function ($q) {
                $q->where('activity_type', 'incoming_inspection')
                  ->orWhere(function ($sq) {
                      $sq->where('activity_type', 'outgoing_inspection')
                         ->whereDoesntHave('inspectionLogs', function ($lq) {
                             $lq->where('is_draft', false);
                         });
                  });
            });
        }

        // Filter by activity_type if provided via request (overrides role filter if conflicting)
        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        $jobs = $query->orderBy('planned_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Get specific inspection job details with default values
     * 
     * CRITICAL RULE (LOCKED):
     * - Incoming inspection: Default values from master_isotank_item_status
     * - Outgoing inspection: Default values from MOST RECENT INCOMING inspection
     * - MUST NOT load from previous outgoing inspections
     */
    public function show($id)
    {
        $job = InspectionJob::with(['isotank'])->findOrFail($id);

        // Check if job is still open
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This inspection job is already completed',
            ], 400);
        }

        $defaultValues = (object)[];

        // PRIORITIZE: If there's an existing draft log for THIS JOB, return its values
        $draftLog = InspectionLog::where('inspection_job_id', $job->id)
            ->where('is_draft', true)
            ->first();

        if ($draftLog) {
            $defaultValues = (object)$draftLog->toArray();
            // Handle timestamps for UI
            foreach (['pressure_1_timestamp', 'pressure_2_timestamp', 'level_1_timestamp', 'level_2_timestamp', 'ibox_temperature_1_timestamp', 'ibox_temperature_2_timestamp'] as $ts) {
                if (!empty($draftLog->$ts)) {
                    $defaultValues->$ts = $draftLog->$ts->format('Y-m-d H:i:s');
                }
            }
        } elseif ($job->activity_type === 'incoming_inspection') {
            // Load condition from master_isotank_item_status
            $itemStatuses = MasterIsotankItemStatus::where('isotank_id', $job->isotank_id)->get();
            foreach ($itemStatuses as $status) {
                $name = $status->item_name;
                $defaultValues->$name = $status->condition;
            }

            // Load measurements from master_isotank_measurement_status
            $measurements = MasterIsotankMeasurementStatus::where('isotank_id', $job->isotank_id)->first();
            if ($measurements) {
                $defaultValues->pressure = $measurements->pressure;
                $defaultValues->level = $measurements->level;
                $defaultValues->temperature = $measurements->temperature;
                $defaultValues->vacuum_value = $measurements->vacuum_mtorr;
                $defaultValues->vacuum_unit = 'mtorr';
            }
        } elseif ($job->activity_type === 'outgoing_inspection') {
            // Load from MOST RECENT INCOMING inspection ONLY
            $lastIncomingInspection = InspectionLog::where('isotank_id', $job->isotank_id)
                ->where('inspection_type', 'incoming_inspection')
                ->where('is_draft', false)
                ->orderBy('inspection_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastIncomingInspection) {
                // Map fields from the last incoming inspection
                // Map fields from the last incoming inspection
                // UPDATED: Use toArray() to ensure ALL columns (including dynamic ones) are copied
                $logArray = $lastIncomingInspection->toArray();
                
                // Exclude fields that shouldn't be copied
                $exclude = ['id', 'uuid', 'inspection_job_id', 'created_at', 'updated_at', 
                           'inspection_date', 'inspector_id', 'signature_path', 'pdf_path',
                           'is_draft', 'receiver_confirmed_at', 'receiver_name', 'receiver_signature_path'];
                
                foreach ($exclude as $field) {
                    unset($logArray[$field]);
                }
                
                // Merge into data
                $data = $logArray;
                // Ensure specific complex mappings are preserved if needed (usually handled by keys matching)


                foreach($data as $k => $v) { $defaultValues->$k = $v; }
                $defaultValues->vacuum_unit = 'mtorr';
            } else {
                // FALLBACK: Load from Master Status if no incoming log found
                // This ensures we have a baseline from Current State
                $itemStatuses = MasterIsotankItemStatus::where('isotank_id', $job->isotank_id)->get();
                foreach ($itemStatuses as $status) {
                    $name = $status->item_name;
                    $defaultValues->$name = $status->condition;
                }

                $measurements = MasterIsotankMeasurementStatus::where('isotank_id', $job->isotank_id)->first();
                if ($measurements) {
                    $defaultValues->pressure = $measurements->pressure;
                    $defaultValues->level = $measurements->level;
                    $defaultValues->temperature = $measurements->temperature;
                    $defaultValues->vacuum_value = $measurements->vacuum_mtorr;
                }
                $defaultValues->vacuum_unit = 'mtorr';
            }
        }

        // NEW: Load Calibration Data from MasterIsotankComponent (Centralized Master)
        $components = MasterIsotankComponent::where('isotank_id', $job->isotank_id)->get();
        $calibrationData = (object)[];

        foreach ($components as $comp) {
            $prefix = '';
            if ($comp->component_type === 'PG') {
                $prefix = 'pressure_gauge';
            } elseif ($comp->component_type === 'PSV') {
                $prefix = 'psv' . $comp->position_code; // e.g., psv1
            } else {
                continue;
            }

            // Map fields
            $serial = $comp->serial_number;
            $calDate = $comp->last_calibration_date ? $comp->last_calibration_date->format('Y-m-d') : null;
            $validUntil = $comp->expiry_date ? $comp->expiry_date->format('Y-m-d') : null;
            
            // Populate calibrationData object (EXTENSIVE)
            $calibrationData->{"{$prefix}_serial"} = $serial;
            $calibrationData->{"{$prefix}_serial_number"} = $serial;
            $calibrationData->{"{$prefix}_sn"} = $serial;
            $calibrationData->{"{$prefix}_calibration_date"} = $calDate;
            $calibrationData->{"{$prefix}_valid_until"} = $validUntil;
            
            // Populate defaultValues for the Form (EXTENSIVE)
            // We provide MULTIPLE naming conventions to ensure Frontend picks it up
            $defaultValues->{"{$prefix}_serial"} = $serial;
            $defaultValues->{"{$prefix}_serial_number"} = $serial;
            $defaultValues->{"{$prefix}_serial_no"} = $serial;
            $defaultValues->{"{$prefix}_sn"} = $serial;
            
            $defaultValues->{"{$prefix}_calibration_date"} = $calDate;
            $defaultValues->{"{$prefix}_valid_until"} = $validUntil;

            // Extra explicit keys for PG if frontend expects 'pg' prefix instead of 'pressure_gauge'
            if ($prefix === 'pressure_gauge') {
                 $defaultValues->pg_serial = $serial;
                 $defaultValues->pg_serial_number = $serial;
                 $defaultValues->pg_sn = $serial;
                 $defaultValues->pg_calibration_date = $calDate;
                 $defaultValues->pg_valid_until = $validUntil;
            }
        }

        // FALLBACK: If MasterIsotankComponent (New) is empty, try MasterIsotankCalibrationStatus (Old/Legacy)
        // This ensures compatibility if the user hasn't migrated everything to the Components table yet.
        $legacyCalibrations = MasterIsotankCalibrationStatus::where('isotank_id', $job->isotank_id)->get();
        foreach ($legacyCalibrations as $cal) {
            $prefix = $cal->item_name === 'pressure_gauge' ? 'pressure_gauge' : $cal->item_name; // e.g. psv1, psv2
            
            // Check if already populated by Component (New system)
            if (isset($defaultValues->{"{$prefix}_serial_number"}) && !empty($defaultValues->{"{$prefix}_serial_number"})) {
                continue;
            }

            $serial = $cal->serial_number;
            $calDate = $cal->calibration_date ? $cal->calibration_date->format('Y-m-d') : null;
            $validUntil = $cal->valid_until ? $cal->valid_until->format('Y-m-d') : null;

            // Populate calibrationData (Legacy - EXTENSIVE)
            $calibrationData->{"{$prefix}_serial"} = $serial;
            $calibrationData->{"{$prefix}_serial_number"} = $serial;
            $calibrationData->{"{$prefix}_sn"} = $serial;
            $calibrationData->{"{$prefix}_calibration_date"} = $calDate;
            $calibrationData->{"{$prefix}_valid_until"} = $validUntil;
            $calibrationData->{"{$prefix}_status"} = $cal->status;

            // Populate defaultValues (Legacy - EXTENSIVE)
            $defaultValues->{"{$prefix}_serial"} = $serial;
            $defaultValues->{"{$prefix}_serial_number"} = $serial;
            $defaultValues->{"{$prefix}_serial_no"} = $serial;
            $defaultValues->{"{$prefix}_sn"} = $serial;
            
            $defaultValues->{"{$prefix}_calibration_date"} = $calDate;
            $defaultValues->{"{$prefix}_valid_until"} = $validUntil;
            
             if ($prefix === 'pressure_gauge') {
                 $defaultValues->pg_serial = $serial;
                 $defaultValues->pg_serial_number = $serial;
                 $defaultValues->pg_sn = $serial;
                 $defaultValues->pg_calibration_date = $calDate;
                 $defaultValues->pg_valid_until = $validUntil;
            }
        }

        // NEW: Ensure Latest Vacuum is loaded from VacuumLog (History) if available
        $latestVacuum = VacuumLog::where('isotank_id', $job->isotank_id)
            ->orderBy('check_datetime', 'desc')
            ->first();

        if ($latestVacuum) {
            // Override vacuum values with the absolute latest log
            // Force float to remove trailing zeros (e.g. 1.1000 -> 1.1)
            $rawVal = $latestVacuum->vacuum_value_mtorr ?? $latestVacuum->vacuum_value_raw;
            $defaultValues->vacuum_value = $rawVal !== null ? (float)$rawVal : null;
            
            $defaultValues->vacuum_temperature = $latestVacuum->temperature;
            $defaultValues->vacuum_unit = 'mtorr';
            $defaultValues->vacuum_check_datetime = $latestVacuum->check_datetime ? $latestVacuum->check_datetime->format('Y-m-d H:i:s') : null;
        }

        // CRITICAL: Ensure destination and receiver from the JOB (Admin Input) are propagated to default values
        // This ensures that when Admin sets 'Destination' and 'Receiver' in the Activity Plan, 
        // the Inspector sees them pre-filled in validity/form fields.
        if (!empty($job->destination)) {
            $defaultValues->destination = $job->destination;
        }
        if (!empty($job->receiver_name)) {
            $defaultValues->receiver_name = $job->receiver_name;
            $defaultValues->receiver = $job->receiver_name; // frontend compatibility alias
        }

        // Get open maintenance jobs for this isotank (READ ONLY)
        $openMaintenance = $job->isotank->maintenanceJobs()
            ->whereIn('status', ['open', 'on_progress', 'not_complete'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'default_values' => $defaultValues,
                'calibration_data' => $calibrationData,
                'open_maintenance' => $openMaintenance,
            ],
        ]);
    }

    /**
     * Get inspection history for specific isotank
     */
    public function history($isotankId)
    {
        $logs = InspectionLog::where('isotank_id', $isotankId)
            ->with(['inspector'])
            ->orderBy('inspection_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
