<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\VacuumSuctionActivity;
use App\Models\MasterIsotank;
use App\Models\VacuumLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacuumSuctionController extends Controller
{
    /**
     * List open vacuum suction activities
     */
    public function index()
    {
        // Get the latest record ID for each isotank that has an uncompleted session
        $sub = VacuumSuctionActivity::select('isotank_id', DB::raw('MAX(id) as max_id'))
            ->whereNull('completed_at')
            ->groupBy('isotank_id');

        $activities = VacuumSuctionActivity::with('isotank')
            ->joinSub($sub, 'latest_records', function ($join) {
                $join->on('vacuum_suction_activities.id', '=', 'latest_records.max_id');
            })
            ->select('vacuum_suction_activities.*')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get activity details
     */
    public function show($id)
    {
        $activity = VacuumSuctionActivity::with(['isotank', 'recorder'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Update/Fill vacuum suction record
     */
    public function update(Request $request, $id)
    {
        $activity = VacuumSuctionActivity::findOrFail($id);

        $rules = [
            'day_number' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ];

        if ($request->day_number == 1) {
            $rules = array_merge($rules, [
                'portable_vacuum_value' => 'nullable|numeric',
                'temperature' => 'nullable|numeric',
                'machine_vacuum_at_start' => 'nullable|numeric',
                'portable_vacuum_when_machine_stops' => 'nullable|numeric',
                'machine_vacuum_at_stop' => 'nullable|numeric',
                'temperature_at_machine_stop' => 'nullable|numeric',
            ]);
        } else {
            $rules = array_merge($rules, [
                'morning_vacuum_value' => 'nullable|numeric',
                'morning_temperature' => 'nullable|numeric',
                'morning_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
                'evening_vacuum_value' => 'nullable|numeric',
                'evening_temperature' => 'nullable|numeric',
                'evening_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            ]);
        }

        $validated = $request->validate($rules);

        // Automatic timestamps for Day 2-5
        if ($request->day_number > 1) {
            if (isset($validated['morning_vacuum_value']) && !isset($validated['morning_timestamp'])) {
                $validated['morning_timestamp'] = now()->toDateTimeString();
            }
            if (isset($validated['evening_vacuum_value']) && !isset($validated['evening_timestamp'])) {
                $validated['evening_timestamp'] = now()->toDateTimeString();
            }
        }

        DB::beginTransaction();
        try {
            // CRITICAL: If day_number changed, we might need a NEW record instead of updating
            if ($activity->day_number != $request->day_number) {
                // Check if a record for this day already exists in the same session
                $existing = VacuumSuctionActivity::where('isotank_id', $activity->isotank_id)
                    ->where('day_number', $request->day_number)
                    ->whereNull('completed_at')
                    ->first();

                if ($existing) {
                    $activity = $existing;
                } else {
                    // Create NEW record for the new day
                    $newRecord = new VacuumSuctionActivity();
                    $newRecord->isotank_id = $activity->isotank_id;
                    $newRecord->recorded_by = $request->user()->id;
                    $newRecord->day_number = $request->day_number;
                    
                    // CARRY OVER Day 1 Data (Initial suction activities)
                    // This ensures session-wide data stays visible in subsequent daily records
                    $newRecord->portable_vacuum_value = $activity->portable_vacuum_value;
                    $newRecord->temperature = $activity->temperature;
                    $newRecord->machine_vacuum_at_start = $activity->machine_vacuum_at_start;
                    $newRecord->portable_vacuum_when_machine_stops = $activity->portable_vacuum_when_machine_stops;
                    $newRecord->machine_vacuum_at_stop = $activity->machine_vacuum_at_stop;
                    $newRecord->temperature_at_machine_stop = $activity->temperature_at_machine_stop;
                    
                    $newRecord->save();
                    $activity = $newRecord;
                }
            }

            $activity->update($validated);
            $activity->recorded_by = $request->user()->id;
            $activity->save();

            if ($request->is_completed) {
                // Mark ALL records for this isotank in current session as completed
                VacuumSuctionActivity::where('isotank_id', $activity->isotank_id)
                    ->whereNull('completed_at')
                    ->update(['completed_at' => now()]);
                
                // Final vacuum log
                $finalVacuum = $activity->evening_vacuum_value ?? $activity->morning_vacuum_value ?? $activity->portable_vacuum_when_machine_stops;
                if ($finalVacuum) {
                    VacuumLog::create([
                        'isotank_id' => $activity->isotank_id,
                        'vacuum_value_raw' => $finalVacuum,
                        'vacuum_unit_raw' => 'mtorr',
                        'vacuum_value_mtorr' => $finalVacuum,
                        'temperature' => $activity->evening_temperature ?? $activity->morning_temperature ?? $activity->temperature_at_machine_stop,
                        'check_datetime' => now(),
                        'source' => 'suction',
                    ]);

                    // Update Master Measurement Status
                    \App\Models\MasterIsotankMeasurementStatus::updateOrCreate(
                        ['isotank_id' => $activity->isotank_id],
                        [
                            'vacuum_mtorr' => (float) $finalVacuum,
                            'temperature' => $activity->evening_temperature ?? $activity->morning_temperature ?? $activity->temperature_at_machine_stop,
                            'last_measurement_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Vacuum suction activity recorded',
                'data' => $activity->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET ACTIVE EVENT (Flutter Compatible)
     */
    public function getActiveEvent($isotankId)
    {
        $activity = VacuumSuctionActivity::with('logs')
            ->where('isotank_id', $isotankId)
            ->whereNull('completed_at')
            ->latest()
            ->first();

        if (!$activity) return response()->json(['success' => true, 'data' => null]);

        // Map to Flutter model structure
        $data = [
            'id' => $activity->id,
            'isotank_id' => $activity->isotank_id,
            'start_time' => $activity->created_at->toDateTimeString(),
            'status' => $activity->status ?? 'ongoing',
            'pre_portable_vacuum' => $activity->portable_vacuum_value ?? 0,
            'pre_isotank_temp' => $activity->temperature ?? 0,
            'start_machine_vacuum' => $activity->machine_vacuum_at_start ?? 0,
            'end_machine_vacuum' => $activity->machine_vacuum_at_stop,
            'post_portable_vacuum' => $activity->portable_vacuum_when_machine_stops,
            'post_isotank_temp' => $activity->temperature_at_machine_stop,
            'logs' => $activity->logs->map(function($l) {
                return [
                    'id' => $l->id,
                    'reading_at' => $l->reading_at->toDateTimeString(),
                    'vacuum_value' => $l->vacuum_value,
                    'temperature' => $l->temperature,
                    'period' => $l->period,
                ];
            }),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * START SUCTION (Flutter Compatible)
     */
    public function startSuction(Request $request)
    {
        $validated = $request->validate([
            'isotank_id' => 'required|exists:master_isotanks,id',
            'pre_portable_vacuum' => 'required|string',
            'pre_portable_unit' => 'nullable|string|in:mtorr,scientific',
            'pre_isotank_temp' => 'required|numeric',
            'start_machine_vacuum' => 'required|string',
        ]);

        $activity = VacuumSuctionActivity::create([
            'isotank_id' => $validated['isotank_id'],
            'day_number' => 1,
            'status' => 'ongoing',
            'portable_vacuum_value' => $validated['pre_portable_vacuum'],
            'portable_vacuum_unit' => $validated['pre_portable_unit'] ?? 'mtorr',
            'temperature' => $validated['pre_isotank_temp'],
            'machine_vacuum_at_start' => $validated['start_machine_vacuum'],
            'machine_start_time' => now(),
            'recorded_by' => $request->user()->id,
        ]);

        return $this->getActiveEvent($activity->isotank_id);
    }

    /**
     * FINISH SUCTION (Flutter Compatible)
     */
    public function finishSuction(Request $request, $id)
    {
        $activity = VacuumSuctionActivity::findOrFail($id);
        
        $validated = $request->validate([
            'end_machine_vacuum' => 'required|string',
            'post_portable_vacuum' => 'required|string',
            'post_portable_unit' => 'nullable|string|in:mtorr,scientific',
            'post_isotank_temp' => 'required|numeric',
        ]);

        $activity->update([
            'machine_vacuum_at_stop' => $validated['end_machine_vacuum'],
            'portable_vacuum_when_machine_stops' => $validated['post_portable_vacuum'],
            'portable_vacuum_stop_unit' => $validated['post_portable_unit'] ?? 'mtorr',
            'temperature_at_machine_stop' => $validated['post_isotank_temp'],
            'machine_stop_time' => now(),
            'status' => 'monitoring',
        ]);

        return response()->json(['success' => true, 'message' => 'Suction finished, monitoring phase started.']);
    }

    /**
     * ADD MONITORING LOG (Flutter Compatible)
     */
    public function addMonitoringLog(Request $request)
    {
        $validated = $request->validate([
            'suction_event_id' => 'required|exists:vacuum_suction_activities,id',
            'vacuum_value' => 'required|string',
            'vacuum_unit' => 'nullable|string|in:mtorr,scientific',
            'temperature' => 'required|numeric',
            'period' => 'required|string',
            'day_number' => 'nullable|integer|min:2|max:10', // New: Explicit Day Selection
        ]);

        $baseActivity = VacuumSuctionActivity::findOrFail($validated['suction_event_id']);

        // FIND DAY 1 ACTIVITY FOR THIS SESSION to calculate offset correctly
        $day1Activity = VacuumSuctionActivity::where('isotank_id', $baseActivity->isotank_id)
            ->whereNull('completed_at')
            ->where('day_number', 1)
            ->first() ?? $baseActivity;

        // Calculate Day Number based on Day 1's created_at (Fallback)
        $startDate = \Carbon\Carbon::parse($day1Activity->created_at)->startOfDay();
        $todayDate = now()->startOfDay();
        $calcDayNumber = 1 + $startDate->diffInDays($todayDate);

        // Preference explicitly from Frontend, else fallback to calculation
        $dayNumber = $validated['day_number'] ?? $calcDayNumber;

        // Ensure we operate on the current active session
        $activity = VacuumSuctionActivity::firstOrCreate(
            [
                'isotank_id' => $baseActivity->isotank_id,
                'day_number' => $dayNumber,
                'completed_at' => null 
            ],
            [
                'recorded_by' => $request->user()->id,
                'status' => 'monitoring',
                // Carry over Day 1 data
                'portable_vacuum_value' => $baseActivity->portable_vacuum_value,
                'temperature' => $baseActivity->temperature,
                'machine_vacuum_at_start' => $baseActivity->machine_vacuum_at_start,
                'portable_vacuum_when_machine_stops' => $baseActivity->portable_vacuum_when_machine_stops,
                'machine_vacuum_at_stop' => $baseActivity->machine_vacuum_at_stop,
                'temperature_at_machine_stop' => $baseActivity->temperature_at_machine_stop,
            ]
        );

        $updates = ['recorded_by' => $request->user()->id];
        $periodReq = strtolower($validated['period']);

        $unitReq = $validated['vacuum_unit'] ?? 'mtorr';

        if (str_contains($periodReq, 'morning') || str_contains($periodReq, 'am')) {
            $updates['morning_vacuum_value'] = $validated['vacuum_value'];
            $updates['morning_vacuum_unit'] = $unitReq;
            $updates['morning_temperature'] = $validated['temperature'];
            $updates['morning_timestamp'] = now()->toDateTimeString();
        } else {
            $updates['evening_vacuum_value'] = $validated['vacuum_value'];
            $updates['evening_vacuum_unit'] = $unitReq;
            $updates['evening_temperature'] = $validated['temperature'];
            $updates['evening_timestamp'] = now()->toDateTimeString();
        }

        $activity->update($updates);

        \App\Models\VacuumMonitoringLog::create([
            'vacuum_suction_activity_id' => $activity->id,
            'vacuum_value' => $validated['vacuum_value'],
            'temperature' => $validated['temperature'],
            'period' => $validated['period'],
            'reading_at' => now(),
        ]);
        // For now, it just adds to history.

        return response()->json(['success' => true, 'message' => 'Log added successfully']);
    }

    /**
     * COMPLETE MONITORING EARLY (Flutter Compatible)
     * Finishes 5-day cycle early on Day 2, 3, or 4 and registers final log.
     */
    public function completeMonitoring(Request $request, $id)
    {
        $baseActivity = VacuumSuctionActivity::findOrFail($id);
        
        // Find ALL activities in this session to mark as complete
        $activities = VacuumSuctionActivity::where('isotank_id', $baseActivity->isotank_id)
            ->whereNull('completed_at')
            ->get();
            
        if ($activities->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No active session found.'], 404);
        }

        // Get the absolute latest record chronologically to use as the final result
        $latestRecord = $activities->sortByDesc('id')->first();
        
        // Mark all as completed
        foreach($activities as $act) {
            $act->completed_at = now();
            $act->status = 'completed';
            $act->save();
        }
        
        // Determine final vacuum value for History Log
        $finalVacuum = $latestRecord->evening_vacuum_value 
            ?? $latestRecord->morning_vacuum_value 
            ?? $latestRecord->portable_vacuum_when_machine_stops;
            
        $finalUnit = $latestRecord->evening_vacuum_unit 
            ?? $latestRecord->morning_vacuum_unit 
            ?? $latestRecord->portable_vacuum_stop_unit
            ?? 'mtorr';
            
        $finalTemp = $latestRecord->evening_temperature 
            ?? $latestRecord->morning_temperature 
            ?? $latestRecord->temperature_at_machine_stop;
            
        // Use 0 as default if scientific notation parser is needed down the line,
        // but store the raw string for exact records
        $numericVacuum = is_numeric($finalVacuum) ? (float) $finalVacuum : 0;

        if ($finalVacuum) {
            \App\Models\VacuumLog::create([
                'isotank_id' => $latestRecord->isotank_id,
                'vacuum_value_raw' => $finalVacuum,
                'vacuum_unit_raw' => $finalUnit,
                'vacuum_value_mtorr' => $numericVacuum, 
                'temperature' => $finalTemp,
                'check_datetime' => now(),
                'source' => 'suction',
                'description' => 'Monitoring Phase Completed (Day ' . $latestRecord->day_number . ')'
            ]);
            
            // Note: master_isotank_measurement_status updater logic usually follows here
            // but keeping it simple based on our plan
        }
        
        return response()->json(['success' => true, 'message' => 'Monitoring session completed successfully.']);
    }

    /**
     * Get grouped monitoring sessions (Flutter Dashboard)
     */
    public function monitoring()
    {
        $allActivities = VacuumSuctionActivity::with(['isotank', 'recorder'])
            ->orderBy('isotank_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $sessions = [];
        $tempSessions = [];

        foreach ($allActivities as $activity) {
            $isoId = $activity->isotank_id;
            
            // Logic: A new session starts IF it's Day 1 OR we don't have an active session for this ISO.
            $shouldStartNew = $activity->day_number == 1 || !isset($tempSessions[$isoId]);

            if ($shouldStartNew) {
                if (isset($tempSessions[$isoId])) {
                    $sessions[] = $tempSessions[$isoId];
                }
                
                $tempSessions[$isoId] = [
                    'isotank' => $activity->isotank,
                    'days' => [ (int)$activity->day_number => $activity ],
                    'is_completed' => (bool)$activity->completed_at,
                    'latest_date' => $activity->created_at,
                    'start_date' => $activity->created_at,
                    'day1_summary' => [
                        'portable_vacuum' => $activity->portable_vacuum_value,
                        'temp' => $activity->temperature,
                        'mch_stop' => $activity->machine_vacuum_at_stop,
                    ]
                ];
            } else {
                $tempSessions[$isoId]['days'][(int)$activity->day_number] = $activity;
                if ($activity->completed_at) {
                    $tempSessions[$isoId]['is_completed'] = true;
                }
                $tempSessions[$isoId]['latest_date'] = $activity->created_at;
                
                if (!$tempSessions[$isoId]['day1_summary']['portable_vacuum'] && $activity->portable_vacuum_value) {
                    $tempSessions[$isoId]['day1_summary']['portable_vacuum'] = $activity->portable_vacuum_value;
                }
                if (!$tempSessions[$isoId]['day1_summary']['mch_stop'] && $activity->machine_vacuum_at_stop) {
                    $tempSessions[$isoId]['day1_summary']['mch_stop'] = $activity->machine_vacuum_at_stop;
                }
            }
        }
        
        foreach($tempSessions as $sess) {
            $sessions[] = $sess;
        }

        // Sort by latest activity date descending
        usort($sessions, function($a, $b) {
            return $b['latest_date'] <=> $a['latest_date'];
        });

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Get Isotanks that have an ongoing/pending vacuum suction activity
     * This is used by the Flutter app to only show relevant isotanks.
     */
    public function getPendingIsotanks()
    {
        $isotanks = \App\Models\MasterIsotank::whereHas('vacuumSuctionActivities', function($q) {
            $q->whereNull('completed_at');
        })->orderBy('iso_number')->get();

        return response()->json([
            'success' => true,
            'data' => $isotanks,
        ]);
    }
}
