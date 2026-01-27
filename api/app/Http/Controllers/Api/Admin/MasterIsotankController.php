<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\InspectionItem;
use App\Models\MasterIsotankItemStatus;

class MasterIsotankController extends Controller
{
    /**
     * Display a listing of isotanks
     */
    public function index(Request $request)
    {
        $query = MasterIsotank::query();

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('iso_number', 'like', "%$search%")
                  ->orWhere('product', 'like', "%$search%")
                  ->orWhere('owner', 'like', "%$search%");
            });
        }

        $isotanks = $query->orderBy('iso_number')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $isotanks,
        ]);
    }

    /**
     * Store a newly created isotank
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'iso_number' => 'required|string|unique:master_isotanks,iso_number',
            'product' => 'nullable|string',
            'owner' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'model_type' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $isotank = MasterIsotank::create($validated);

        // AUTO-GENERATE ITEM STATUSES for the new Isotank
        // Fetch all active inspection items
        $items = InspectionItem::where('is_active', true)->get();
        foreach ($items as $item) {
            MasterIsotankItemStatus::create([
                'isotank_id' => $isotank->id,
                'item_name' => $item->code, // Using 'code' as 'item_name' for consistency
                'condition' => 'na',        // Default to 'na' (Not Available)
                'description' => $item->label,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Isotank created successfully',
            'data' => $isotank,
        ], 201);
    }

    /**
     * Display the specified isotank
     */
    public function show($id)
    {
        $isotank = MasterIsotank::with([
            'classSurveys',
            'itemStatuses',
            'lastInspectionLog.inspector',
            'lastMaintenanceJob',
            'lastVacuumLog',
            'inspectionLogs' => function($q) {
                $q->latest()->take(5);
            },
            'maintenanceJobs' => function($q) {
                $q->latest()->take(5);
            },
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $isotank,
        ]);
    }

    /**
     * Update the specified isotank
     */
    public function update(Request $request, $id)
    {
        $isotank = MasterIsotank::findOrFail($id);

        $validated = $request->validate([
            'iso_number' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('master_isotanks', 'iso_number')->ignore($isotank->id),
            ],
            'product' => 'nullable|string',
            'owner' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'model_type' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $isotank->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Isotank updated successfully',
            'data' => $isotank,
        ]);
    }

    /**
     * Remove the specified isotank
     */
    public function destroy($id)
    {
        $isotank = MasterIsotank::findOrFail($id);
        $isotank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Isotank deleted successfully',
        ]);
    }

    /**
     * Activate isotank
     */
    public function activate($id)
    {
        $isotank = MasterIsotank::findOrFail($id);
        $isotank->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Isotank activated successfully',
            'data' => $isotank,
        ]);
    }

    /**
     * Deactivate isotank
     */
    public function deactivate($id)
    {
        $isotank = MasterIsotank::findOrFail($id);
        $isotank->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Isotank deactivated successfully',
            'data' => $isotank,
        ]);
    }

    /**
     * Get only active isotanks
     */
    public function active()
    {
        $isotanks = MasterIsotank::active()->orderBy('iso_number')->get();

        return response()->json([
            'success' => true,
            'data' => $isotanks,
        ]);
    }
}
