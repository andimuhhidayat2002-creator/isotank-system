<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceJobController extends Controller
{
    /**
     * Get all maintenance jobs
     * 
     * RULES:
     * - Maintenance role can view all jobs
     * - Filter by status
     */
    public function index(Request $request)
    {
        $query = MaintenanceJob::with(['isotank']);

        // Default to active jobs if no status specified
        if (!$request->has('status')) {
            $query->where('status', '!=', 'closed');
        } else {
            $query->where('status', $request->status);
        }

        // Filter by isotank ID
        if ($request->has('isotank_id')) {
            $query->where('isotank_id', $request->isotank_id);
        }

        // Search by ISO Number (Server Side)
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('isotank', function($q) use ($search) {
                $q->where('iso_number', 'LIKE', "%{$search}%");
            });
        }

        $jobs = $query->orderBy('status', 'asc') // prioritizing open/progress
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Get specific maintenance job
     */
    public function show($id)
    {
        $job = MaintenanceJob::with(['isotank', 'triggeredByInspection'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Update maintenance job status
     * 
     * CRITICAL RULE:
     * - ONLY maintenance activity can close maintenance
     * - Inspection NEVER closes maintenance
     * - Calibration NEVER closes maintenance
     * - Vacuum suction NEVER closes maintenance
     */
    public function updateStatus(Request $request, $id)
    {
        $job = MaintenanceJob::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,on_progress,not_complete,closed,deferred',
            'work_description' => 'required_if:status,closed|string',
            'after_photo' => 'nullable|image|max:5120', // Max 5MB
            'completed_by' => 'nullable|integer|exists:users,id',
            'completed_at' => 'nullable|date',
            'sparepart' => 'nullable|string',
            'qty' => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'status' => $validated['status'],
            ];

            if (isset($validated['work_description'])) {
                $updateData['work_description'] = $validated['work_description'];
            }

            if ($request->hasFile('after_photo')) {
                $path = $request->file('after_photo')->store('maintenance', 'public');
                $updateData['after_photo'] = $path;
            }
            
            if (isset($validated['sparepart'])) {
                $updateData['sparepart'] = $validated['sparepart'];
            }
            
            if (isset($validated['qty'])) {
                $updateData['qty'] = $validated['qty'];
            }

            if ($validated['status'] === 'closed') {
                $updateData['completed_by'] = $validated['completed_by'] ?? $request->user()->id;
                $updateData['completed_at'] = $validated['completed_at'] ?? now();
            }

            $job->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance job updated successfully',
                'data' => $job->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get maintenance history for specific isotank
     */
    public function history($isotankId)
    {
        $jobs = MaintenanceJob::where('isotank_id', $isotankId)
            ->with(['completedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }
}
