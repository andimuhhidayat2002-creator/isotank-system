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
                $q->with('inspector')->latest()->take(10);
            },
            'maintenanceJobs' => function($q) {
                $q->latest()->take(10);
            },
            // Load ACTIVE maintenance jobs specifically
            'maintenanceJobs' => function($q) {
                $q->whereIn('status', ['open', 'on_progress', 'not_complete', 'pending'])->latest();
            }
        ])->findOrFail($id);

        // ROBUST VIEW GENERATION: Ensure 1:1 match with Category Rules
        // Always rebuild item list based on Tank Category to ensure we show exactly what is required.
        $category = $isotank->tank_category ?? 'T75';
        $desiredItems = \App\Models\InspectionItem::where('is_active', true)
            ->whereJsonContains('applicable_categories', $category)
            ->orderBy('order') // Ensure correct display order
            ->get();
            
        $log = $isotank->lastInspectionLog;
        $logData = null;
        if ($log) {
            $logData = is_array($log->inspection_data) ? $log->inspection_data : json_decode($log->inspection_data, true);
        }
        
        $masterStatuses = $isotank->itemStatuses->keyBy('item_name');
        
        $finalConditions = [];
        foreach ($desiredItems as $item) {
             // Priority 1: Data from latest inspection log (Most accurate representation of "Latest")
             // Priority 2: Data from Master Status table
             // Priority 3: 'na'
             
             $val = null;
             
             // Check Log first (if available) for immediate consistency
             if ($logData) {
                 $code = $item->code;
                 $uCode = str_replace([' ', '.', '/'], '_', $code);
                 
                 $val = $logData[$code] ?? $logData[$uCode] ?? ($log->{$code} ?? null);
             }
             
             // Check Master Status as fallback
             if ($val === null) {
                 $val = $masterStatuses[$item->code]->condition ?? null;
             }
             
             $finalConditions[] = [
                 'item_name' => $item->code,
                 'description' => $item->label,
                 'condition' => $val ?? 'na',
                 'last_inspection_date' => $log->inspection_date ?? $isotank->updated_at,
             ];
             
             // INJECT ATTRIBUTES (1:1 with Inspection Log Detail)
             // Only if we have log data to show
             if ($log) {
                 $attr = [];
                 
                 // IBOX System Details
                 if ($item->code === 'ibox_condition') {
                     $attr[] = ['Battery', $log->ibox_battery_percent ? $log->ibox_battery_percent .' %' : '-'];
                     $attr[] = ['Pressure (Digital)', $log->ibox_pressure ? $log->ibox_pressure .' Bar' : '-'];
                     $attr[] = ['Temperature #1 (Digital)', $log->ibox_temperature_1 ? $log->ibox_temperature_1 .' °C' : ($log->ibox_temperature ? $log->ibox_temperature .' °C' : '-')];
                     $attr[] = ['Temperature #2 (Digital)', $log->ibox_temperature_2 ? $log->ibox_temperature_2 .' °C' : '-'];
                     $attr[] = ['Level (Digital)', $log->ibox_level ? $log->ibox_level .' %' : '-'];
                 }
                 
                 // Pressure Gauge Details
                 if ($item->code === 'pressure_gauge_condition') {
                     $attr[] = ['Serial Number', $log->pressure_gauge_serial_number ?? '-'];
                     $attr[] = ['Calibration Date', $log->pressure_gauge_calibration_date ? $log->pressure_gauge_calibration_date->format('Y-m-d') : '-'];
                     $attr[] = ['Reading (Pressure 1)', $log->pressure_1 ? $log->pressure_1 .' MPa': '-'];
                     $attr[] = ['Reading (Pressure 2)', $log->pressure_2 ? $log->pressure_2 .' MPa': '-'];
                 }
                 
                 // Level Gauge Details
                 if ($item->code === 'level_gauge_condition') {
                     $attr[] = ['Level Gauge Condition', $log->level_gauge_condition ? strtoupper($log->level_gauge_condition) : '-'];
                     $attr[] = ['Reading (Level 1)', $log->level_1 ? $log->level_1 .' mm' : '-'];
                     $attr[] = ['Reading (Level 2)', $log->level_2 ? $log->level_2 .' mm' : '-'];
                 }
                 
                 // Vacuum System (Inject under Vacuum Gauge Condition usually)
                 if ($item->code === 'vacuum_gauge_condition') {
                      // Move Vacuum details to be injected AFTER 'vacuum_gauge_condition'
                      // However, user list shows them at end of section F. 
                      // Since we are looping items, attaching to vacuum_gauge_condition is the safest anchor.
                      $attr[] = ['Vacuum Value', $log->vacuum_value ? $log->vacuum_value . ' ' . ($log->vacuum_unit ?? 'mTorr') : '-'];
                      $attr[] = ['Vacuum Temperature', $log->vacuum_temperature ? $log->vacuum_temperature . ' °C' : '-'];
                      $attr[] = ['Check Datetime', $log->vacuum_check_datetime ? Carbon\Carbon::parse($log->vacuum_check_datetime)->format('Y-m-d H:i') : '-'];
                 }

                 // PSV Details (1-4) - SIMPLIFIED LOGIC
                 if (Str::startsWith($item->code, 'psv') && Str::endsWith($item->code, '_condition')) {
                     // Extract number: psv1_condition -> 1
                     $num = substr($item->code, 3, 1); 
                     $p = 'psv'.$num;
                     
                     // STATUS | SN Line
                     $status = $log->{$p.'_status'} ? strtoupper($log->{$p.'_status'}) : '-';
                     $sn = $log->{$p.'_serial_number'} ?? '-';
                     $attr[] = ['STATUS: ' . $status . ' | SN: ' . $sn, '']; // Value empty, Key contains info
                     
                     // Cal Date | Valid Until Line
                     $cal = $log->{$p.'_calibration_date'} ? $log->{$p.'_calibration_date'}->format('Y-m-d') : '-';
                     $valid = $log->{$p.'_valid_until'} ? $log->{$p.'_valid_until'}->format('Y-m-d') : '-';
                     $attr[] = ['Cal. Date: ' . $cal . ' | Valid Until: ' . $valid, ''];
                 }
                 
                 // Add attributes as "Pseudo Items"
                 foreach ($attr as $a) {
                     $finalConditions[] = [
                         'item_name' => $item->code . '_attr_' . Str::slug($a[0]),
                         'description' => $a[0], // Exact Label from User Request
                         'condition' => $a[1], // The value
                         'is_attribute' => true, // Flag for potential UI styling
                         'last_inspection_date' => $log->inspection_date
                     ];
                 }
             }
        }
        
        // If we found valid items, use them. Otherwise (e.g. legacy T75 with no items defined?), fall back to existing.
        if (count($finalConditions) > 0) {
            $isotank->setRelation('itemStatuses', collect($finalConditions));
        }

        // Get Active Maintenance jobs separately to ensure we have them clearly
        $activeMaintenance = \App\Models\MaintenanceJob::where('isotank_id', $id)
            ->whereIn('status', ['open', 'on_progress', 'pending'])
            ->get();
            
        // Flatten Class Survey (Calibration)
        $latestSurvey = $isotank->classSurveys->sortByDesc('survey_date')->first();

        // Prepare Response Data
        $responseData = $isotank->toArray();
        $responseData['active_maintenance_jobs'] = $activeMaintenance;
        $responseData['latest_class_survey'] = $latestSurvey;
        $responseData['has_active_maintenance'] = $activeMaintenance->isNotEmpty();

        return response()->json([
            'success' => true,
            'data' => $responseData,
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
