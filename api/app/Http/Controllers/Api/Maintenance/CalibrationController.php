<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\CalibrationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalibrationController extends Controller
{
    /**
     * List all assigned calibration jobs (planned or in_progress)
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // optional filter

        $query = CalibrationLog::with(['isotank'])
            ->whereIn('status', ['planned', 'in_progress']);

        if ($status) {
            $query->where('status', $status);
        }

        $jobs = $query->orderBy('planned_date', 'asc')->get();

        return response()->json($jobs);
    }

    /**
     * Show details of a specific calibration job
     */
    public function show($id)
    {
        $job = CalibrationLog::with(['isotank', 'creator', 'performer'])->findOrFail($id);
        return response()->json($job);
    }

    /**
     * Complete or update the calibration job
     */
    public function update(Request $request, $id)
    {
        // Maintenance user completes the calibration
        // Expected inputs: status (completed/rejected), calibration_date, valid_until, certificate/notes?
        
        $request->validate([
            'status' => 'required|in:completed,rejected',
            'calibration_date' => 'required_if:status,completed|date',
            'valid_until' => 'required_if:status,completed|date|after:calibration_date',
            'replacement_serial' => 'required_if:status,rejected|string',
            'replacement_calibration_date' => 'required_if:status,rejected|date',
            'replacement_valid_until' => 'required_if:status,rejected|date|after:replacement_calibration_date',
            'notes' => 'nullable|string',
        ]);

        $job = CalibrationLog::findOrFail($id);

        if ($job->status === 'completed' || $job->status === 'rejected') {
            return response()->json(['message' => 'Job already completed'], 400);
        }

        $updateData = [
            'status' => $request->status,
            'notes' => $request->notes,
            'performed_by' => auth()->id(), // logged in maintenance user
            'updated_at' => now(),
        ];

        if ($request->status === 'completed') {
            $updateData['calibration_date'] = $request->calibration_date;
            $updateData['valid_until'] = $request->valid_until;
        } else if ($request->status === 'rejected') {
            $updateData['replacement_serial'] = $request->replacement_serial;
            $updateData['replacement_calibration_date'] = $request->replacement_calibration_date;
            // Note: We don't have replacement_valid_until column in log table specifically named that, 
            // but usually a replacement means the NEW item is now calibrated. 
            // In the migration we have 'valid_until' which is for the log. 
            // Logic: 
            // - If completed: The ORIGINAL item is valid until X.
            // - If rejected: The REPLACEMENT item is valid until Y.
            // So we can still use 'valid_until' to store the validity of the *current* valid item on the tank.
            // But let's check migration. 
            // Migration has: 'calibration_date', 'valid_until', 'replacement_calibration_date'.
            // It does NOT have 'replacement_valid_until'. 
            // However, the MasterIsotankCalibrationStatus needs to know when the *tank's* calibration expires.
            // Whether it's the original item (passed) or new item (replaced), the tank is now valid until Z.
            // So we will store the replacement's validity in 'valid_until' as well, or we can treat 'valid_until' as the overall expiry.
            // Let's use 'valid_until' for the final expiry date resulting from this job.
            
            $updateData['calibration_date'] = $request->replacement_calibration_date; // effectively the date of calibration for the active item
            $updateData['valid_until'] = $request->replacement_valid_until;
        }

        $job->update($updateData);
        
        // Update Master Status (valid for both cases: passed or replaced with new valid item)
        // If rejected (replaced), the tank is now valid because of the new item.
        \App\Models\MasterIsotankCalibrationStatus::updateOrCreate(
            [
                'isotank_id' => $job->isotank_id,
                'item_name' => $job->item_name // CRITICALLY IMPORTANT: Use item_name to distinguish
            ],
            [
                'status' => 'valid',
                'serial_number' => $request->status === 'completed' ? $job->serial_number : $request->replacement_serial, // Update SN if replaced
                'calibration_date' => $request->status === 'completed' ? $request->calibration_date : $request->replacement_calibration_date,
                'valid_until' => $request->status === 'completed' ? $request->valid_until : $request->replacement_valid_until,
                'certificate_number' => $job->id 
            ]
        );

        return response()->json(['message' => 'Calibration updated successfully', 'data' => $job]);
    }
}
