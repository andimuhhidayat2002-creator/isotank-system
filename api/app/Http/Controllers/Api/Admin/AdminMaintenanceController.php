<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceJob;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ADMIN MAINTENANCE CONTROLLER
 * 
 * Handles:
 * 1. Manual maintenance job creation (admin input)
 * 2. Maintenance job listing and management
 * 
 * Note: Excel bulk upload handled by BulkMaintenanceUploadController
 */
class AdminMaintenanceController extends Controller
{
    /**
     * List all maintenance jobs
     */
    public function index(Request $request)
    {
        $query = MaintenanceJob::with(['isotank', 'creator', 'assignee', 'triggeredByInspection']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by isotank
        if ($request->has('isotank_id')) {
            $query->where('isotank_id', $request->isotank_id);
        }

        $maintenanceJobs = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $maintenanceJobs,
        ]);
    }

    /**
     * Show single maintenance job
     */
    public function show($id)
    {
        $maintenanceJob = MaintenanceJob::with([
            'isotank',
            'creator',
            'assignee',
            'completedBy',
            'triggeredByInspection'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $maintenanceJob,
        ]);
    }

    /**
     * Create maintenance job manually (admin input)
     * 
     * Required fields:
     * - isotank_id
     * - source_item
     * - description
     * 
     * Optional fields:
     * - priority
     * - planned_date
     * - assigned_to
     * - before_photo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'isotank_id' => 'required|exists:master_isotanks,id',
            'source_item' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,critical',
            'planned_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'before_photo' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Validate isotank exists and is active
            $isotank = MasterIsotank::findOrFail($validated['isotank_id']);
            
            if ($isotank->status !== 'active') {
                throw new \Exception('Cannot create maintenance for inactive isotank');
            }

            // Handle photo upload
            if ($request->hasFile('before_photo')) {
                $validated['before_photo'] = $request->file('before_photo')->store('maintenance', 'public');
            }

            // Create maintenance job
            $maintenanceJob = MaintenanceJob::create([
                'isotank_id' => $validated['isotank_id'],
                'source_item' => $validated['source_item'],
                'description' => $validated['description'],
                'priority' => $validated['priority'] ?? 'medium',
                'planned_date' => $validated['planned_date'] ?? null,
                'status' => 'open',
                'before_photo' => $validated['before_photo'] ?? null,
                'created_by' => $request->user()->id,
                'assigned_to' => $validated['assigned_to'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance job created successfully',
                'data' => $maintenanceJob->load(['isotank', 'creator', 'assignee']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update maintenance job
     * 
     * Note: Only certain fields can be updated
     */
    public function update(Request $request, $id)
    {
        $maintenanceJob = MaintenanceJob::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,critical',
            'planned_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $maintenanceJob->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance job updated successfully',
            'data' => $maintenanceJob->load(['isotank', 'creator', 'assignee']),
        ]);
    }

    /**
     * Update maintenance status and handle closure
     * 
     * CRITICAL: Only maintenance activity can close maintenance
     */
    public function updateStatus(Request $request, $id)
    {
        $maintenanceJob = MaintenanceJob::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,on_progress,not_complete,closed',
            'work_description' => 'nullable|string',
            'photo_during' => 'nullable|image|max:5120',
            'after_photo' => 'nullable|image|max:5120',
            'sparepart' => 'nullable|string',
            'qty' => 'nullable|integer',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'status' => $validated['status'],
            ];

            // Handle photos
            if ($request->hasFile('photo_during')) {
                $updateData['photo_during'] = $request->file('photo_during')->store('maintenance', 'public');
            }

            if ($request->hasFile('after_photo')) {
                $updateData['after_photo'] = $request->file('after_photo')->store('maintenance', 'public');
            }

            // Handle closure
            if ($validated['status'] === 'closed') {
                $updateData['completed_at'] = now();
                $updateData['completed_by'] = $request->user()->id;
                $updateData['work_description'] = $validated['work_description'] ?? null;
                $updateData['sparepart'] = $validated['sparepart'] ?? null;
                $updateData['qty'] = $validated['qty'] ?? null;
            }

            $maintenanceJob->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance status updated successfully',
                'data' => $maintenanceJob->load(['isotank', 'creator', 'assignee', 'completedBy']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
