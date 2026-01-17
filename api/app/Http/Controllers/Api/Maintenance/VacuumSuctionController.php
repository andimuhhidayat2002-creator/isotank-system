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
}
