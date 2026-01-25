<?php

namespace App\Http\Controllers\Api\Inspector;

use App\Http\Controllers\Controller;
use App\Models\InspectionJob;
use App\Models\InspectionLog;
use App\Models\MaintenanceJob;
use App\Models\MasterIsotankItemStatus;
use App\Models\MasterLatestInspection;
use App\Models\ReceiverConfirmation;
use App\Models\VacuumLog;
use App\Models\VacuumSuctionActivity;
use App\Services\PdfGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionSubmitController extends Controller
{
    /**
     * Submit inspection (IMMUTABLE - INSERT ONLY)
     * 
     * CRITICAL RULES (LOCKED):
     * 1. Inspection logs are IMMUTABLE (insert-only)
     * 2. EVERY submit ALWAYS creates a NEW inspection_logs record
     * 3. Inspection NEVER closes maintenance
     * 4. Maintenance triggered ONLY by condition meaning change
     * 5. Vacuum suction triggered when vacuum_value > 8 mTorr
     * 6. Master tables updated ONLY by backend system logic
     * 7. Database is the SINGLE SOURCE OF TRUTH
     * 8. Vacuum normalized to mTorr (torr = value * 1000, mtorr = value, scientific = scientific notation of Torr)
     */
    public function submit(Request $request, $jobId)
    {
        $job = InspectionJob::with(['isotank'])->findOrFail($jobId);

        // Validate job is still open
        if ($job->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This inspection job is already completed',
            ], 400);
        }

        // Validate isotank is active
        if ($job->isotank->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit inspection for inactive isotank',
            ], 400);
        }

        // CRITICAL: Vacuum validity check (11 months)
        $latestVacuum = VacuumLog::where('isotank_id', $job->isotank_id)
            ->orderBy('check_datetime', 'desc')
            ->first();
        
        if ($latestVacuum) {
            $expirationDate = $latestVacuum->check_datetime->addMonths(11);
            if (now()->gt($expirationDate) && empty($request->vacuum_value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacuum check has expired (11 months). You MUST perform a new vacuum check before submitting this inspection.',
                ], 400);
            }
        }

        $isDraft = filter_var($request->is_draft, FILTER_VALIDATE_BOOLEAN);
        $category = $job->isotank->tank_category ?? 'T75';
        $isT75 = ($category === 'T75');
        
        // T75 Specific Required Logic
        // If it's T75 and not draft -> Required.
        // If it's T11/T50 -> Nullable (because they use dynamic items instead)
        $t75Required = ($isT75 && !$isDraft) ? 'required' : 'nullable';

        // Validation rules
        $rules = [
            'inspection_date' => 'required|date',
            
            // B. GENERAL CONDITION (Legacy T75)
            'surface' => $t75Required . '|in:good,not_good,need_attention,na',
            'frame' => $t75Required . '|in:good,not_good,need_attention,na',
            'tank_plate' => $t75Required . '|in:good,not_good,need_attention,na',
            'venting_pipe' => $t75Required . '|in:good,not_good,need_attention,na',
            'explosion_proof_cover' => $t75Required . '|in:good,not_good,need_attention,na',
            'grounding_system' => $t75Required . '|in:good,not_good,need_attention,na',
            'document_container' => $t75Required . '|in:good,not_good,need_attention,na',
            'safety_label' => $t75Required . '|in:good,not_good,need_attention,na',
            'valve_box_door' => $t75Required . '|in:good,not_good,need_attention,na',
            'valve_box_door_handle' => $t75Required . '|in:good,not_good,need_attention,na',
            
            // C. VALVE & PIPE SYSTEM (Legacy T75)
            'valve_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            'valve_position' => $t75Required . '|in:correct,incorrect',
            'pipe_joint' => $t75Required . '|in:good,not_good,need_attention,na',
            'air_source_connection' => $t75Required . '|in:good,not_good,need_attention,na',
            'esdv' => $t75Required . '|in:good,not_good,need_attention,na',
            'blind_flange' => $t75Required . '|in:good,not_good,need_attention,na',
            'prv' => $t75Required . '|in:good,not_good,need_attention,na',
            
            // D. IBOX SYSTEM
            'ibox_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            'pressure' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'level' => 'nullable|numeric',
            'battery_percent' => 'nullable|integer|min:0|max:100',
            
            // E. INSTRUMENT (outgoing has multi-stage)
            'pressure_gauge_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            'pressure_gauge_serial' => 'nullable|string',
            'pressure_gauge_calibration_date' => 'nullable|date',
            'pressure_gauge_valid_until' => 'nullable|date',
            'pressure_1' => 'nullable|numeric',
            'pressure_1_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            'pressure_2' => 'nullable|numeric',
            'pressure_2_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            
            'level_gauge_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            'level_1' => 'nullable|numeric',
            'level_1_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            'level_2' => 'nullable|numeric',
            'level_2_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            
            'ibox_temperature_1' => 'nullable|numeric',
            'ibox_temperature_1_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            'ibox_temperature_2' => 'nullable|numeric',
            'ibox_temperature_2_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            
            // F. VACUUM
            'vacuum_value' => 'nullable|numeric',
            'vacuum_unit' => 'nullable|in:torr,mtorr,scientific',
            'vacuum_temperature' => 'nullable|numeric',
            'vacuum_check_datetime' => 'nullable|date_format:Y-m-d H:i:s',
            'vacuum_gauge_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            'vacuum_port_suction_condition' => $t75Required . '|in:good,not_good,need_attention,na',
            
            // G. PSV (1-4)
            'psv1_condition' => 'nullable|in:good,not_good,need_attention,na',
            'psv1_serial' => 'nullable|string',
            'psv1_calibration_date' => 'nullable|date',
            'psv1_valid_until' => 'nullable|date',
            'psv1_status' => 'nullable|in:valid,expired,rejected',
            'psv1_replacement_serial' => 'nullable|string',
            'psv1_replacement_calibration_date' => 'nullable|date',
            
            'psv2_condition' => 'nullable|in:good,not_good,need_attention,na',
            'psv2_serial' => 'nullable|string',
            'psv2_calibration_date' => 'nullable|date',
            'psv2_valid_until' => 'nullable|date',
            'psv2_status' => 'nullable|in:valid,expired,rejected',
            'psv2_replacement_serial' => 'nullable|string',
            'psv2_replacement_calibration_date' => 'nullable|date',
            
            'psv3_condition' => 'nullable|in:good,not_good,need_attention,na',
            'psv3_serial' => 'nullable|string',
            'psv3_calibration_date' => 'nullable|date',
            'psv3_valid_until' => 'nullable|date',
            'psv3_status' => 'nullable|in:valid,expired,rejected',
            'psv3_replacement_serial' => 'nullable|string',
            'psv3_replacement_calibration_date' => 'nullable|date',
            
            'psv4_condition' => 'nullable|in:good,not_good,need_attention,na',
            'psv4_serial' => 'nullable|string',
            'psv4_calibration_date' => 'nullable|date',
            'psv4_valid_until' => 'nullable|date',
            'psv4_status' => 'nullable|in:valid,expired,rejected',
            'psv4_replacement_serial' => 'nullable|string',
            'psv4_replacement_calibration_date' => 'nullable|date',
            
            // Outgoing specific
            'destination' => ($isDraft ? 'nullable' : ($job->activity_type === 'outgoing_inspection' ? 'required' : 'nullable')) . '|string',
            
            // Photos (outgoing)
            'photo_front' => 'nullable|image|max:5120',
            'photo_back' => 'nullable|image|max:5120',
            'photo_left' => 'nullable|image|max:5120',
            'photo_right' => 'nullable|image|max:5120',
            'photo_inside_valve_box' => 'nullable|image|max:5120',
            'photo_additional' => 'nullable|image|max:5120',
            'photo_extra' => 'nullable|image|max:5120',
            
            'is_draft' => 'nullable',
            
            // Filling Status
            'filling_status_code' => 'nullable|string',
            'filling_status_desc' => 'nullable|string',
            
            // Dynamic Data
            'inspection_data' => 'nullable|json',
        ];

        // DYNAMIC RULES: Add validation for active InspectionItems
        $dynamicItems = []; // Store full objects for type checking
        try {
            if (class_exists(\App\Models\InspectionItem::class)) {
                $dynamicItems = \App\Models\InspectionItem::where('is_active', true)->get();
                foreach ($dynamicItems as $item) {
                     $code = $item->code;
                     // PHP replaces spaces and dots with underscores in request keys. 
                     // Also handle slashes which are common in our item codes.
                     $inputKey = str_replace([' ', '.', '/'], '_', $code);
                     
                     if (!isset($rules[$inputKey]) && !isset($rules[$code])) {
                         // Determine rule based on input_type
                         if ($item->input_type === 'number') {
                             $rules[$inputKey] = 'nullable|numeric';
                         } elseif ($item->input_type === 'date') {
                             $rules[$inputKey] = 'nullable|date';
                         } elseif ($item->input_type === 'text') {
                             $rules[$inputKey] = 'nullable|string|max:1000';
                         } else {
                             // condition (enum) or boolean
                             $rules[$inputKey] = 'nullable|string'; // Allow string to support flexible inputs, specific validation optional
                         }
                     }
                }
            }
        } catch (\Exception $e) {}

        // Also allow dynamic photos from items if any
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'photo_') && !isset($rules[$key])) {
                $rules[$key] = 'nullable|image|max:5120';
            }
        }

        // CRITICAL FIX: Ensure inspection_date is always present
        // If not provided by Flutter, use current date
        if (!$request->has('inspection_date') || empty($request->inspection_date)) {
            $request->merge(['inspection_date' => now()->toDateString()]); // YYYY-MM-DD format
        }

        $validated = $request->validate($rules);
        $allInput = $request->all(); // Ensure $allInput is available

        // Handle File Storage - Dynamic for ALL photos (outgoing + items)
        foreach ($allInput as $key => $value) {
            if (str_starts_with($key, 'photo_')) {
                if ($request->hasFile($key)) {
                    // It's a new upload - store it
                    $path = $request->file($key)->store('inspections', 'public');
                    $validated[$key] = $path;
                    $allInput[$key] = $path; // Important: Update allInput so triggerMaintenance receives the string path
                } else {
                    // It's likely a string (existing path from draft), keep it
                    $validated[$key] = $value;
                }
            }
        }


        DB::beginTransaction();

        try {
            $isDraft = filter_var($request->is_draft, FILTER_VALIDATE_BOOLEAN);
            
            // Check for existing draft for this job
            $inspectionLog = InspectionLog::where('inspection_job_id', $job->id)
                ->where('is_draft', true)
                ->first();

            // PRE-SUBMIT LOGIC: Handle Timestamps & Stage 2 Validation for Outgoing
            if ($job->activity_type === 'outgoing_inspection') {
                $currentTime = now();
                
                // PG
                if (!empty($request->pressure_1) && (!$inspectionLog || empty($inspectionLog->pressure_1_timestamp))) {
                    $validated['pressure_1_timestamp'] = $currentTime;
                } elseif ($inspectionLog && !empty($inspectionLog->pressure_1_timestamp)) {
                    $validated['pressure_1_timestamp'] = $inspectionLog->pressure_1_timestamp;
                }

                if (!empty($request->pressure_2)) {
                    if (empty($validated['pressure_1_timestamp'])) {
                        throw new \Exception("Pressure 1 must be filled before Pressure 2.");
                    }
                    $diff = $currentTime->diffInHours($validated['pressure_1_timestamp']);
                    if ($diff < 6) {
                        throw new \Exception("Pressure 2 can only be filled 6 hours after Pressure 1. Current difference: {$diff} hours.");
                    }
                    if (!$inspectionLog || empty($inspectionLog->pressure_2_timestamp)) {
                        $validated['pressure_2_timestamp'] = $currentTime;
                    } else {
                        $validated['pressure_2_timestamp'] = $inspectionLog->pressure_2_timestamp;
                    }
                }

                // LG
                if (!empty($request->level_1) && (!$inspectionLog || empty($inspectionLog->level_1_timestamp))) {
                    $validated['level_1_timestamp'] = $currentTime;
                } elseif ($inspectionLog && !empty($inspectionLog->level_1_timestamp)) {
                    $validated['level_1_timestamp'] = $inspectionLog->level_1_timestamp;
                }

                if (!empty($request->level_2)) {
                    if (empty($validated['level_1_timestamp'])) {
                        throw new \Exception("Level 1 must be filled before Level 2.");
                    }
                    $diff = $currentTime->diffInHours($validated['level_1_timestamp']);
                    if ($diff < 6) {
                        throw new \Exception("Level 2 can only be filled 6 hours after Level 1.");
                    }
                    if (!$inspectionLog || empty($inspectionLog->level_2_timestamp)) {
                        $validated['level_2_timestamp'] = $currentTime;
                    } else {
                        $validated['level_2_timestamp'] = $inspectionLog->level_2_timestamp;
                    }
                }

                // IBOX Temperature
                if (!empty($request->ibox_temperature_1) && (!$inspectionLog || empty($inspectionLog->ibox_temperature_1_timestamp))) {
                    $validated['ibox_temperature_1_timestamp'] = $currentTime;
                } elseif ($inspectionLog && !empty($inspectionLog->ibox_temperature_1_timestamp)) {
                    $validated['ibox_temperature_1_timestamp'] = $inspectionLog->ibox_temperature_1_timestamp;
                }

                if (!empty($request->ibox_temperature_2)) {
                    if (empty($validated['ibox_temperature_1_timestamp'])) {
                        throw new \Exception("Temperature 1 must be filled before Temperature 2.");
                    }
                    $diff = $currentTime->diffInHours($validated['ibox_temperature_1_timestamp']);
                    if ($diff < 6) {
                        throw new \Exception("Temperature 2 can only be filled 6 hours after Temperature 1.");
                    }
                    if (!$inspectionLog || empty($inspectionLog->ibox_temperature_2_timestamp)) {
                        $validated['ibox_temperature_2_timestamp'] = $currentTime;
                    } else {
                        $validated['ibox_temperature_2_timestamp'] = $inspectionLog->ibox_temperature_2_timestamp;
                    }
                }
            }

            $logData = [
                'inspection_job_id' => $job->id,
                'isotank_id' => $job->isotank_id,
                'inspection_type' => $job->activity_type,
                'inspection_date' => $validated['inspection_date'],
                'inspector_id' => $request->user()->id,
                'is_draft' => $isDraft,
                
                // All inspection items (Safely handle nullable draft fields)
                'surface' => $validated['surface'] ?? null,
                'frame' => $validated['frame'] ?? null,
                'tank_plate' => $validated['tank_plate'] ?? null,
                'venting_pipe' => $validated['venting_pipe'] ?? null,
                'explosion_proof_cover' => $validated['explosion_proof_cover'] ?? null,
                'grounding_system' => $validated['grounding_system'] ?? null,
                'document_container' => $validated['document_container'] ?? null,
                'safety_label' => $validated['safety_label'] ?? null,
                'valve_box_door' => $validated['valve_box_door'] ?? null,
                'valve_box_door_handle' => $validated['valve_box_door_handle'] ?? null,
                
                'valve_condition' => $validated['valve_condition'] ?? null,
                'valve_position' => $validated['valve_position'] ?? null,
                'pipe_joint' => $validated['pipe_joint'] ?? null,
                'air_source_connection' => $validated['air_source_connection'] ?? null,
                'esdv' => $validated['esdv'] ?? null,
                'blind_flange' => $validated['blind_flange'] ?? null,
                'prv' => $validated['prv'] ?? null,
                
                'ibox_condition' => $validated['ibox_condition'] ?? null,
                'ibox_pressure' => $validated['pressure'] ?? null,
                'ibox_temperature' => $validated['temperature'] ?? null,
                'ibox_level' => $validated['level'] ?? null,
                'ibox_battery_percent' => $validated['battery_percent'] ?? null,
                
                'pressure_gauge_condition' => $validated['pressure_gauge_condition'] ?? null,
                'pressure_gauge_serial_number' => $validated['pressure_gauge_serial'] ?? $validated['pressure_gauge_serial_number'] ?? $validated['pg_serial'] ?? $validated['pg_serial_number'] ?? $validated['pg_sn'] ?? null,
                'pressure_gauge_calibration_date' => $validated['pressure_gauge_calibration_date'] ?? null,
                'pressure_gauge_valid_until' => $validated['pressure_gauge_valid_until'] ?? null,
                'pressure_1' => $validated['pressure_1'] ?? null,
                'pressure_1_timestamp' => $validated['pressure_1_timestamp'] ?? null,
                'pressure_2' => $validated['pressure_2'] ?? null,
                'pressure_2_timestamp' => $validated['pressure_2_timestamp'] ?? null,
                
                'level_gauge_condition' => $validated['level_gauge_condition'] ?? null,
                'level_1' => $validated['level_1'] ?? null,
                'level_1_timestamp' => $validated['level_1_timestamp'] ?? null,
                'level_2' => $validated['level_2'] ?? null,
                'level_2_timestamp' => $validated['level_2_timestamp'] ?? null,
                
                'ibox_temperature_1' => $validated['ibox_temperature_1'] ?? null,
                'ibox_temperature_1_timestamp' => $validated['ibox_temperature_1_timestamp'] ?? null,
                'ibox_temperature_2' => $validated['ibox_temperature_2'] ?? null,
                'ibox_temperature_2_timestamp' => $validated['ibox_temperature_2_timestamp'] ?? null,
                
                'vacuum_value' => $validated['vacuum_value'] ?? null,
                'vacuum_unit' => $validated['vacuum_unit'] ?? null,
                'vacuum_temperature' => $validated['vacuum_temperature'] ?? null,
                'vacuum_check_datetime' => $validated['vacuum_check_datetime'] ?? null,
                'vacuum_gauge_condition' => $validated['vacuum_gauge_condition'] ?? null,
                'vacuum_port_suction_condition' => $validated['vacuum_port_suction_condition'] ?? null,
                
                'psv1_condition' => $validated['psv1_condition'] ?? null,
                'psv1_serial_number' => $validated['psv1_serial'] ?? $validated['psv1_serial_number'] ?? $validated['psv1_sn'] ?? null,
                'psv1_calibration_date' => $validated['psv1_calibration_date'] ?? null,
                'psv1_valid_until' => $validated['psv1_valid_until'] ?? null,
                'psv1_status' => $validated['psv1_status'] ?? null,
                'psv1_replacement_serial' => $validated['psv1_replacement_serial'] ?? null,
                'psv1_replacement_calibration_date' => $validated['psv1_replacement_calibration_date'] ?? null,
                
                'psv2_condition' => $validated['psv2_condition'] ?? null,
                'psv2_serial_number' => $validated['psv2_serial'] ?? $validated['psv2_serial_number'] ?? $validated['psv2_sn'] ?? null,
                'psv2_calibration_date' => $validated['psv2_calibration_date'] ?? null,
                'psv2_valid_until' => $validated['psv2_valid_until'] ?? null,
                'psv2_status' => $validated['psv2_status'] ?? null,
                'psv2_replacement_serial' => $validated['psv2_replacement_serial'] ?? null,
                'psv2_replacement_calibration_date' => $validated['psv2_replacement_calibration_date'] ?? null,
                
                'psv3_condition' => $validated['psv3_condition'] ?? null,
                'psv3_serial_number' => $validated['psv3_serial'] ?? $validated['psv3_serial_number'] ?? $validated['psv3_sn'] ?? null,
                'psv3_calibration_date' => $validated['psv3_calibration_date'] ?? null,
                'psv3_valid_until' => $validated['psv3_valid_until'] ?? null,
                'psv3_status' => $validated['psv3_status'] ?? null,
                'psv3_replacement_serial' => $validated['psv3_replacement_serial'] ?? null,
                'psv3_replacement_calibration_date' => $validated['psv3_replacement_calibration_date'] ?? null,
                
                'psv4_condition' => $validated['psv4_condition'] ?? null,
                'psv4_serial_number' => $validated['psv4_serial'] ?? $validated['psv4_serial_number'] ?? $validated['psv4_sn'] ?? null,
                'psv4_calibration_date' => $validated['psv4_calibration_date'] ?? null,
                'psv4_valid_until' => $validated['psv4_valid_until'] ?? null,
                'psv4_status' => $validated['psv4_status'] ?? null,
                'psv4_replacement_serial' => $validated['psv4_replacement_serial'] ?? null,
                'psv4_replacement_calibration_date' => $validated['psv4_replacement_calibration_date'] ?? null,
                
                'destination' => $validated['destination'] ?? null,
                'receiver_name' => $job->receiver_name,
                
                'photo_front' => $validated['photo_front'] ?? null,
                'photo_back' => $validated['photo_back'] ?? null,
                'photo_left' => $validated['photo_left'] ?? null,
                'photo_right' => $validated['photo_right'] ?? null,
                'photo_inside_valve_box' => $validated['photo_inside_valve_box'] ?? null,
                'photo_additional' => $validated['photo_additional'] ?? null,
                'photo_extra' => $validated['photo_extra'] ?? null,
                'additional_details' => collect($allInput)->filter(function($v, $k) {
                    return str_starts_with($k, 'remark_') || (str_starts_with($k, 'photo_') && !in_array($k, [
                        'photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_inside_valve_box', 'photo_additional', 'photo_extra'
                    ]));
                })->toArray(),
                
                // Filling Status
                // Filling Status (Fallback to Job data or Isotank current status if not provided)
                'filling_status_code' => $validated['filling_status_code'] ?? $job->filling_status_code ?? $job->isotank->filling_status_code,
                'filling_status_desc' => $validated['filling_status_desc'] ?? $job->filling_status_desc ?? $job->isotank->filling_status_desc,
                
                // Dynamic Inspection Items Data
                'inspection_data' => (function() use ($request, $validated, $dynamicItems) {
                    $data = $request->input('inspection_data') ? json_decode($request->input('inspection_data'), true) : [];
                    if (!is_array($data)) $data = [];
                    
                    // Add top-level dynamic fields to inspection_data
                    foreach ($dynamicItems as $item) {
                        $code = $item->code;
                        $inputKey = str_replace([' ', '.', '/'], '_', $code);
                        
                        if (isset($validated[$inputKey])) {
                            $data[$code] = $validated[$inputKey];
                        } elseif (isset($validated[$code])) {
                             $data[$code] = $validated[$code];
                        }
                    }
                    return !empty($data) ? json_encode($data) : null;
                })(),
            ];

            if ($inspectionLog) {
                $inspectionLog->update($logData);
            } else {
                $inspectionLog = InspectionLog::create($logData);
            }

            // CRITICAL: Draft does NOT update master tables, maintenance, or calibration
            if (!$isDraft) {
                // 2. TRIGGER MAINTENANCE (ONLY if condition meaning changed)
                $this->triggerMaintenance($job->isotank_id, $validated, $inspectionLog, $allInput);

                // 3. UPDATE MASTER ITEM STATUS (Backend logic only)
                $this->updateMasterItemStatus($job->isotank_id, $validated, $inspectionLog->id);

                // 4. UPDATE MASTER MEASUREMENT STATUS
                $this->updateMasterMeasurementStatus($job->isotank_id, $validated);

                // 5. UPDATE MASTER CALIBRATION STATUS
                $this->updateMasterCalibrationStatus($job->isotank_id, $validated);

                // 6. VACUUM LOG (Update or Create based on Datetime)
                if (!empty($validated['vacuum_value']) && !empty($validated['vacuum_check_datetime'])) {
                    $mtorr = $this->normalizeVacuum($validated['vacuum_value'], $validated['vacuum_unit'] ?? 'mtorr');
                    
                    // Logic: If datetime matches existing log for this isotank, update it. If new datetime, create new.
                    VacuumLog::updateOrCreate(
                        [
                            'isotank_id' => $job->isotank_id,
                            'check_datetime' => $validated['vacuum_check_datetime'],
                        ],
                        [
                            'vacuum_value_raw' => $validated['vacuum_value'],
                            'vacuum_unit_raw' => $validated['vacuum_unit'] ?? 'mtorr',
                            'vacuum_value_mtorr' => $mtorr,
                            'temperature' => $validated['vacuum_temperature'] ?? null,
                            'source' => 'inspection',
                        ]
                    );

                    // 7. TRIGGER VACUUM SUCTION ACTIVITY (if vacuum_value > 8 mTorr)
                    if ($mtorr > 8) {
                        $this->triggerVacuumSuction($job->isotank_id, $mtorr, $validated['vacuum_temperature'] ?? null);
                    }
                }

                // 8. MARK JOB AS DONE
                // Only incoming_inspection is done immediately.
                // Outgoing remains 'open' for receiver confirmation.
                if ($job->activity_type === 'incoming_inspection') {
                    $job->update(['status' => 'done']);
                    
                    // UPDATE MASTER ISOTANK FILLING STATUS (for incoming)
                    $isotankUpdates = [];
                    if (!empty($validated['filling_status_code'])) {
                        $isotankUpdates['filling_status_code'] = $validated['filling_status_code'];
                    }
                    if (!empty($validated['filling_status_desc'])) {
                        $isotankUpdates['filling_status_desc'] = $validated['filling_status_desc'];
                    }
                    if (!empty($isotankUpdates)) {
                        $job->isotank->update($isotankUpdates);
                    }
                }

                // 9. UPDATE MASTER LATEST INSPECTION (SNAPSHOT)
                $this->updateMasterLatestInspection($job->isotank_id, $inspectionLog);
                
                // 9b. UPDATE MASTER ITEM STATUSES (SYNC WITH INSPECTION LOG)
                $this->updateMasterItemStatuses($job->isotank_id, $validated, $inspectionLog);
                
                // 10. AUTO-GENERATE PDF
                // For INCOMING: Generate immediately.
                // For OUTGOING: Generate ONLY after Receiver Confirmation (to include receiver notes).
                try {
                    // ONLY generate PDF immediately for incoming inspections.
                    // Outgoing inspections must wait for receiver confirmation.
                    if ($job->activity_type === 'incoming_inspection') {
                        $pdfService = new PdfGenerationService();
                        $pdfPath = $pdfService->generateIncomingPdf($inspectionLog);
                    }
                } catch (\Exception $pdfError) {
                    // Log error but don't fail the submission
                    \Log::error('PDF generation failed: ' . $pdfError->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection submitted successfully',
                'data' => [
                    'inspection_log' => $inspectionLog,
                    'job_status' => $job->status,
                    'pdf_path' => $inspectionLog->pdf_path ?? null,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit inspection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * MAINTENANCE TRIGGER LOGIC (CRITICAL)
     * 
     * Triggered ONLY when condition meaning changes:
     * - good → not_good → YES
     * - good → need_attention → YES
     * - need_attention → not_good → YES
     * - not_good → not_good → NO (unless new evidence)
     */
    private function triggerMaintenance($isotankId, $validated, $inspectionLog, $allInput = [])
    {
        // Items that trigger maintenance
        $maintenanceItems = [
            'surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover',
            'grounding_system', 'document_container', 'safety_label', 'valve_box_door',
            'valve_box_door_handle', 'valve_condition', 'pipe_joint', 'air_source_connection',
            'esdv', 'blind_flange', 'prv', 'ibox_condition', 'pressure_gauge_condition',
            'vacuum_gauge_condition', 'vacuum_port_suction_condition',
            'psv1_condition', 'psv2_condition', 'psv3_condition', 'psv4_condition',
        ];

        // Get previous condition from master_isotank_item_status
        $previousStatuses = MasterIsotankItemStatus::where('isotank_id', $isotankId)
            ->get()
            ->keyBy('item_name');

        foreach ($maintenanceItems as $item) {
            if (!isset($validated[$item])) {
                continue;
            }

            $newCondition = $validated[$item];
            $oldCondition = $previousStatuses->get($item)?->condition ?? 'good';

            // Check if maintenance should be triggered
            $shouldTrigger = false;
            
            // "na" never triggers maintenance itself, but transitioning FROM "na" to bad/need_attention does.
            if ($newCondition === 'na') {
                 $shouldTrigger = false;
            } 
            // Degradation Logic:
            elseif (in_array($oldCondition, ['good', 'na']) && in_array($newCondition, ['not_good', 'need_attention'])) {
                $shouldTrigger = true;
            } elseif ($oldCondition === 'need_attention' && $newCondition === 'not_good') {
                $shouldTrigger = true;
            } elseif ($oldCondition === 'not_good' && $newCondition === 'not_good') {
                 // Already bad, don't re-trigger unless explicitly requested (could be handled by a "force" flag if needed)
                 $shouldTrigger = false;
            }

            if ($shouldTrigger) {
                // Create maintenance job
                $remark = $allInput["remark_{$item}"] ?? "Condition changed from {$oldCondition} to {$newCondition}";
                
                // Determine photo path - first try specific item photo, then fallback
                // Determine photo path - Check specific item photo first
                $photoPath = null;
                if (isset($allInput["photo_{$item}"])) {
                     $val = $allInput["photo_{$item}"];
                     if (is_string($val)) {
                         // It's already a path string (processed in main loop)
                         $photoPath = $val;
                     } elseif ($val instanceof \Illuminate\Http\UploadedFile) {
                         // Safety fallback: It's still a file, upload it now
                         $photoPath = $val->store('inspections', 'public');
                     }
                }

                // Fallback to generic photos if specific one not found
                if (!$photoPath) {
                     $photoPath = $this->getPhotoForItem($item, $validated);
                }

                MaintenanceJob::create([
                    'isotank_id' => $isotankId,
                    'source_item' => $item,
                    'description' => $remark,
                    'status' => 'open',
                    'triggered_by_inspection_log_id' => $inspectionLog->id,
                    'before_photo' => $photoPath,
                ]);
            }
        }
    }

    /**
     * UPDATE MASTER ITEM STATUS (Backend logic only)
     */
    private function updateMasterItemStatus($isotankId, $validated, $inspectionLogId)
    {
        $standardItems = [
            'surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover',
            'grounding_system', 'document_container', 'safety_label', 'valve_box_door',
            'valve_box_door_handle', 'valve_condition', 'valve_position', 'pipe_joint',
            'air_source_connection', 'esdv', 'blind_flange', 'prv', 'ibox_condition',
            'pressure_gauge_condition', 'level_gauge_condition',
            'vacuum_gauge_condition', 'vacuum_port_suction_condition',
            'psv1_condition', 'psv2_condition', 'psv3_condition', 'psv4_condition',
        ];

        // Fetch dynamic items if available
        $dynamicItems = [];
        try {
            if (class_exists(\App\Models\InspectionItem::class)) {
                $dynamicItems = \App\Models\InspectionItem::where('is_active', true)->pluck('code')->toArray();
            }
        } catch (\Exception $e) {
            // Ignore if model not found or db error
        }

        $allItems = array_unique(array_merge($standardItems, $dynamicItems));

        foreach ($allItems as $item) {
            // Check direct column
            $val = $validated[$item] ?? null;
            
            // Or check inside inspection_data JSON
            if (!$val && isset($validated['inspection_data']) && is_array($validated['inspection_data'])) {
                $val = $validated['inspection_data'][$item] ?? null;
            } else if (!$val && isset($validated['inspection_data']) && is_string($validated['inspection_data'])) {
                 $json = json_decode($validated['inspection_data'], true);
                 $val = $json[$item] ?? null;
            }

            if ($val) {
                MasterIsotankItemStatus::updateOrCreate(
                    [
                        'isotank_id' => $isotankId,
                        'item_name' => $item,
                    ],
                    [
                        'condition' => $val,
                        'last_inspection_date' => now(),
                        'last_inspection_log_id' => $inspectionLogId,
                    ]
                );
            }
        }
    }

    /**
     * UPDATE MASTER MEASUREMENT STATUS (Backend logic only)
     */
    private function updateMasterMeasurementStatus($isotankId, $validated)
    {
        $mtorr = null;
        if (!empty($validated['vacuum_value'])) {
            $mtorr = $this->normalizeVacuum($validated['vacuum_value'], $validated['vacuum_unit'] ?? 'mtorr');
        }

        \App\Models\MasterIsotankMeasurementStatus::updateOrCreate(
            ['isotank_id' => $isotankId],
            [
                'pressure' => $validated['pressure'] ?? null,
                'level' => $validated['level'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'vacuum_mtorr' => $mtorr,
                'last_measurement_at' => now(),
            ]
        );
    }

    /**
     * UPDATE MASTER CALIBRATION STATUS (Backend logic only)
     */
    private function updateMasterCalibrationStatus($isotankId, $validated)
    {
        \Log::info("=== UPDATE MASTER CALIBRATION STATUS ===", [
            'isotank_id' => $isotankId,
            'validated_keys' => array_keys($validated),
        ]);

        $calibrationItems = [
            'pressure_gauge' => [
                'serial' => 'pressure_gauge_serial',
                'date' => 'pressure_gauge_calibration_date',
                'valid' => 'pressure_gauge_valid_until',
            ],
            'psv1' => [
                'serial' => 'psv1_serial',
                'date' => 'psv1_calibration_date',
                'valid' => 'psv1_valid_until',
                'status' => 'psv1_status',
            ],
            'psv2' => [
                'serial' => 'psv2_serial',
                'date' => 'psv2_calibration_date',
                'valid' => 'psv2_valid_until',
                'status' => 'psv2_status',
            ],
            'psv3' => [
                'serial' => 'psv3_serial',
                'date' => 'psv3_calibration_date',
                'valid' => 'psv3_valid_until',
                'status' => 'psv3_status',
            ],
            'psv4' => [
                'serial' => 'psv4_serial',
                'date' => 'psv4_calibration_date',
                'valid' => 'psv4_valid_until',
                'status' => 'psv4_status',
            ],
        ];

        foreach ($calibrationItems as $item => $fields) {
            $serialKey = $fields['serial'];
            $hasSerial = isset($validated[$serialKey]);
            
            \Log::info("Checking calibration item: {$item}", [
                'serial_key' => $serialKey,
                'has_serial' => $hasSerial,
                'serial_value' => $validated[$serialKey] ?? 'NOT SET',
                'date_value' => $validated[$fields['date']] ?? 'NOT SET',
            ]);

            if ($hasSerial && !empty($validated[$serialKey])) {
                $updateData = [
                    'serial_number' => $validated[$serialKey],
                    'calibration_date' => $validated[$fields['date']] ?? null,
                    'valid_until' => $validated[$fields['valid']] ?? null,
                    'status' => $validated[$fields['status'] ?? ''] ?? 'valid',
                ];

                \Log::info("Updating master calibration for {$item}", $updateData);

                \App\Models\MasterIsotankCalibrationStatus::updateOrCreate(
                    [
                        'isotank_id' => $isotankId,
                        'item_name' => $item,
                    ],
                    $updateData
                );

                \Log::info("Successfully updated master calibration for {$item}");

                // CRITICAL: Trigger calibration activity if rejected
                if (($validated[$fields['status'] ?? ''] ?? 'valid') === 'rejected') {
                    \App\Models\CalibrationLog::create([
                        'isotank_id' => $isotankId,
                        'item_name' => $item,
                        'description' => "Triggered by inspection rejection of serial " . $validated[$serialKey],
                        'status' => 'planned',
                        'created_by' => auth()->id(),
                    ]);
                    
                    \Log::info("Created calibration log for rejected {$item}");
                }
            } else {
                \Log::info("Skipping {$item} - no serial number provided");
            }
        }

        \Log::info("=== END UPDATE MASTER CALIBRATION STATUS ===");
    }

    /**
     * Normalize vacuum value to mTorr
     */
    private function normalizeVacuum($value, $unit)
    {
        switch ($unit) {
            case 'torr':
                return $value * 1000;
            case 'scientific':
                // Assuming scientific is Torr in scientific notation
                return $value * 1000;
            case 'mtorr':
            default:
                return $value;
        }
    }

    /**
     * TRIGGER VACUUM SUCTION ACTIVITY (when vacuum_value > 8 mTorr)
     */
    private function triggerVacuumSuction($isotankId, $vacuumValue, $temperature)
    {
        // Check if there's already an active vacuum suction activity
        VacuumSuctionActivity::updateOrCreate(
            ['isotank_id' => $isotankId, 'completed_at' => null],
            [
                'day_number' => 1,
                'portable_vacuum_value' => $vacuumValue,
                'temperature' => $temperature,
            ]
        );
    }

    /**
     * Get photo for specific item (helper)
     */
    private function getPhotoForItem($item, $validated)
    {
        // Map items to photos (simplified - can be enhanced)
        return $validated['photo_inside_valve_box'] ?? $validated['photo_additional'] ?? null;
    }

    /**
     * UPDATE MASTER LATEST INSPECTION (SNAPSHOT)
     */
    private function updateMasterLatestInspection($isotankId, $log)
    {
        $data = $log->toArray();
        // Remove fields that shouldn't be in the snapshot or cause issues
        unset($data['id'], $data['inspection_job_id'], $data['created_at'], $data['updated_at']);
        
        $data['inspection_log_id'] = $log->id;

        MasterLatestInspection::updateOrCreate(
            ['isotank_id' => $isotankId],
            $data
        );
    }

    /**
     * UPDATE MASTER ITEM STATUSES
     * Sync all inspection items (including dynamic) to master_isotank_item_statuses
     */
    private function updateMasterItemStatuses($isotankId, $validated, $inspectionLog)
    {
        // Get all active inspection items from database
        $masterItems = \App\Models\InspectionItem::where('is_active', true)->get();
        
        // Get inspection_data if exists
        $inspectionData = $inspectionLog->inspection_data;
        if (is_string($inspectionData)) {
            $inspectionData = json_decode($inspectionData, true);
        }
        if (!is_array($inspectionData)) {
            $inspectionData = [];
        }
        
        // Collect all items to sync
        $itemsToSync = [];
        
        // 1. Sync dynamic items from InspectionItem model
        foreach ($masterItems as $item) {
            $code = $item->code;
            $value = $validated[$code] ?? $inspectionLog->$code ?? $inspectionData[$code] ?? null;
            
            if ($value && in_array(strtolower($value), ['good', 'not_good', 'need_attention', 'na', 'correct', 'incorrect', 'yes', 'no', 'valid', 'expired'])) {
                $itemsToSync[$code] = $value;
            }
        }
        
        // 2. Sync hardcoded items (D-G sections)
        $hardcodedItems = [
            'ibox_condition',
            'pressure_gauge_condition',
            'level_gauge_condition',
            'vacuum_gauge_condition',
            'vacuum_port_suction_condition',
            'psv1_condition',
            'psv2_condition',
            'psv3_condition',
            'psv4_condition',
        ];
        
        foreach ($hardcodedItems as $code) {
            $value = $validated[$code] ?? $inspectionLog->$code ?? $inspectionData[$code] ?? null;
            
            if ($value && in_array(strtolower($value), ['good', 'not_good', 'need_attention', 'na', 'correct', 'incorrect', 'yes', 'no', 'valid', 'expired'])) {
                $itemsToSync[$code] = $value;
            }
        }
        
        // 3. Update or create records in master_isotank_item_statuses
        foreach ($itemsToSync as $itemName => $condition) {
            \App\Models\MasterIsotankItemStatus::updateOrCreate(
                [
                    'isotank_id' => $isotankId,
                    'item_name' => $itemName,
                ],
                [
                    'condition' => $condition,
                    'updated_at' => now(),
                ]
            );
        }
        
        \Log::info("Master Item Statuses synced", [
            'isotank_id' => $isotankId,
            'items_count' => count($itemsToSync),
        ]);
    }

    /**
     * Receiver confirmation (for outgoing inspection)
     * 
     * UPDATED RULES (USER REQUEST):
     * 1. Receiver confirms ONLY general condition items (B) - 10 items
     * 2. For each item: ACCEPT or REJECT
     * 3. Optional: remark + photo per item
     * 4. Data stored in receiver_confirmations table (INSERT ONLY, immutable)
     * 5. REJECT items are ONLY NOTED (documented) - NOT blocking
     * 6. Job status ALWAYS set to 'done' after confirmation
     * 7. Location ALWAYS updated after confirmation (regardless of ACCEPT/REJECT)
     */
    public function receiverConfirm(Request $request, $jobId)
    {
        $job = InspectionJob::with(['isotank'])->findOrFail($jobId);

        if ($job->activity_type !== 'outgoing_inspection') {
            return response()->json([
                'success' => false,
                'message' => 'Only outgoing inspections require receiver confirmation',
            ], 400);
        }

        // Validate confirmations based on ACTIVE DYNAMIC ITEMS (Single Source of Truth)
        $dynamicItems = \App\Models\InspectionItem::where('is_active', true)
            ->where(function($q) {
                 $q->where('category', 'like', 'b%')
                   ->orWhere('category', 'like', '%general%')
                   ->orWhere('category', 'external');
            })
            ->orderBy('order')
            ->get();

        $generalConditionItems = $dynamicItems->pluck('code')->toArray();
        
        $rules = [];
        foreach ($generalConditionItems as $item) {
            $rules["confirmations.{$item}.decision"] = 'required|in:ACCEPT,REJECT';
            $rules["confirmations.{$item}.remark"] = 'nullable|string|max:500';
            $rules["confirmations.{$item}.photo"] = 'nullable|image|max:5120';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Find the active inspection log for this job
            $inspectionLog = InspectionLog::where('inspection_job_id', $job->id)
                ->where('is_draft', false)
                ->latest()
                ->first();

            if (!$inspectionLog) {
                throw new \Exception("Initial inspection log not found for this job.");
            }

            // Check if confirmations already exist (immutability check)
            $existingConfirmations = ReceiverConfirmation::where('inspection_log_id', $inspectionLog->id)->count();
            if ($existingConfirmations > 0) {
                throw new \Exception("Receiver confirmations already exist for this inspection. They cannot be modified.");
            }

            $acceptCount = 0;
            $rejectCount = 0;
            $confirmations = [];

            // Process each item confirmation
            foreach ($generalConditionItems as $item) {
                $decision = $validated['confirmations'][$item]['decision'];
                $remark = $validated['confirmations'][$item]['remark'] ?? null;
                
                // Handle photo upload
                $photoPath = null;
                if ($request->hasFile("confirmations.{$item}.photo")) {
                    $photoPath = $request->file("confirmations.{$item}.photo")->store('receiver_confirmations', 'public');
                }

                // Get inspector's condition for this item
                $inspectorCondition = $inspectionLog->$item ?? 'na';

                // Create receiver confirmation record (INSERT ONLY)
                $confirmation = ReceiverConfirmation::create([
                    'inspection_log_id' => $inspectionLog->id,
                    'item_name' => $item,
                    'inspector_condition' => $inspectorCondition,
                    'receiver_decision' => $decision,
                    'receiver_remark' => $remark,
                    'receiver_photo_path' => $photoPath,
                ]);

                $confirmations[] = $confirmation;

                // Count decisions (for reporting only)
                if ($decision === 'ACCEPT') {
                    $acceptCount++;
                } else {
                    $rejectCount++;
                }
            }

            // Handle Receiver Signature (REQUIRED for Outgoing Inspection)
            $receiverSignaturePath = null;
            if ($request->hasFile('receiver_signature')) {
                $receiverSignaturePath = $request->file('receiver_signature')->store('receiver_signatures', 'public');
            } else {
                 throw new \Exception("Receiver signature is required.");
            }

            // Update inspection log with receiver confirmation timestamp and signature
            $inspectionLog->update([
                'receiver_confirmed_at' => now(),
                'receiver_signature_path' => $receiverSignaturePath,
                'receiver_signed_at' => now(),
            ]);

            // UDPATE RECEIVER NAME FROM LOGGED IN USER (User Request)
            $job->update([
                'receiver_name' => $request->user()->name
            ]);

            // ALWAYS mark job as done (The process is finished, result is either Accepted or Rejected)
        $job->update(['status' => 'done']);
        
        // FILLING / CONTENT STATUS RULE (FINAL – LOCKED)
        // If ALL items = ACCEPT: Update location AND Confirm filling
        // If ANY item = REJECT: Do NOT update location, Do NOT update filling
        if ($rejectCount === 0) {
            $updates = [];
            
            if ($job->destination) {
                $updates['location'] = $job->destination;
            }
            
            if ($job->filling_status_code) {
                $updates['filling_status_code'] = $job->filling_status_code;
                $updates['filling_status_desc'] = $job->filling_status_desc;
            }
            
            if (!empty($updates)) {
                $job->isotank->update($updates);
            }

            // CRITICAL: CLEAR YARD POSITION
            // The isotank has officially left the yard (destination updated).
            // We must remove it from any yard slot so it becomes "Unplaced" when it returns.
            \App\Models\IsotankPosition::where('isotank_id', $job->isotank_id)->delete();
        }


            // AUTO-GENERATE OUTGOING PDF (Extension Requirement)
            try {
                $pdfService = new PdfGenerationService();
                $pdfPath = $pdfService->generateOutgoingPdf($inspectionLog);
            } catch (\Exception $pdfError) {
                // Log error but don't fail the confirmation
                \Log::error('Outgoing PDF generation failed: ' . $pdfError->getMessage());
            }

            DB::commit();

            // Build message based on decisions
            $message = "Receiver confirmation completed. ";
            if ($rejectCount > 0) {
                $message .= "{$rejectCount} item(s) rejected (noted for documentation). ";
            }
            $message .= "Inspection completed and location updated.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'accept_count' => $acceptCount,
                    'reject_count' => $rejectCount,
                    'job_status' => $job->status,
                    'location_updated' => true,
                    'confirmations' => $confirmations,
                    'pdf_path' => $inspectionLog->pdf_path ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record confirmation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inspection details for receiver (outgoing only)
     * 
     * Returns inspection data with general condition items for receiver to review
     */
    public function getInspectionForReceiver($jobId)
    {
        $job = InspectionJob::with(['isotank', 'inspector'])->findOrFail($jobId);

        if ($job->activity_type !== 'outgoing_inspection') {
            return response()->json([
                'success' => false,
                'message' => 'Only outgoing inspections require receiver confirmation',
            ], 400);
        }

        // Find the inspection log
        $inspectionLog = InspectionLog::where('inspection_job_id', $job->id)
            ->where('is_draft', false)
            ->latest()
            ->first();

        if (!$inspectionLog) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection not yet submitted by inspector',
            ], 404);
        }

        // Check if already confirmed
        $existingConfirmations = ReceiverConfirmation::where('inspection_log_id', $inspectionLog->id)->count();
        $alreadyConfirmed = $existingConfirmations > 0;

        // Get general condition items DYNAMICALLY from Master Data
    // Matches the logic in Inspection Form and Report View for Category B
    $dynamicItems = \App\Models\InspectionItem::where('is_active', true)
        ->where(function($q) {
             $q->where('category', 'like', 'b%')
               ->orWhere('category', 'like', '%general%')
               ->orWhere('category', 'external')
               // Also include formerly 'safety' items if they are now mapped to G but user wants 'General' consistency
               // But usually we just follow 'General'. If safety were moved to B, they are covered.
               ;
        })
        ->orderBy('order')
        ->get();

    $items = [];
    // Ensure inspection_data is accessible as array
    $logData = $inspectionLog->inspection_data;
    if (is_string($logData)) {
        $logData = json_decode($logData, true) ?? [];
    }
    if (!is_array($logData)) $logData = [];

    foreach ($dynamicItems as $dItem) {
        $key = $dItem->code;
        // Check availability in log (try json first, then column fallback)
        $val = $logData[$key] ?? ($inspectionLog->$key ?? 'na');
        
        // Skip null values if desired? No, receiver should see everything inspector saw.
        // Format condition
        $fmt = strtoupper(str_replace('_', ' ', $val));
        if ($val === null || $val === '') {
             $val = 'na';
             $fmt = 'N/A';
        }

        $items[] = [
            'key' => $key,
            'name' => $dItem->label,
            'inspector_condition' => $val,
            'inspector_condition_formatted' => $fmt,
        ];
    }

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job,
                'isotank' => $job->isotank,
                'inspector' => $job->inspector,
                'inspection_date' => $inspectionLog->inspection_date,
                'destination' => $inspectionLog->destination,
                'receiver_name' => $job->receiver_name,
                'items' => $items,
                'already_confirmed' => $alreadyConfirmed,
            ],
        ]);
    }

    /**
     * Upload PDF report for inspection
     */
    public function uploadPdf(Request $request, $jobId)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        $job = InspectionJob::findOrFail($jobId);

        // Find the inspection log for this job
        $inspectionLog = InspectionLog::where('inspection_job_id', $job->id)
            ->where('is_draft', false)
            ->latest()
            ->first();

        if (!$inspectionLog) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection log not found for this job',
            ], 404);
        }

        try {
            // Store PDF file
            $pdfPath = $request->file('pdf')->store('inspection_pdfs', 'public');

            // Update inspection log with PDF path
            $inspectionLog->update(['pdf_path' => $pdfPath]);
            
            // CRITICAL: Also update Master Latest Inspection Snapshot
            // This ensures the dashboard sees the new PDF immediately
            MasterLatestInspection::updateOrCreate(
                ['isotank_id' => $job->isotank_id],
                ['pdf_path' => $pdfPath]
            );

            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded successfully',
                'data' => [
                    'pdf_path' => $pdfPath,
                    'pdf_url' => asset('storage/' . $pdfPath),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
