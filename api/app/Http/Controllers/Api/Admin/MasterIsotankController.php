<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

                 // Helper for Value Extraction (Model -> JSON -> Default)
                 $getV = function($k) use ($log, $logData) {
                     return $log->{$k} ?? $logData[$k] ?? null;
                 };
                 // Helper for Date Extraction
                 $getD = function($k) use ($log, $logData) {
                     $d = $log->{$k} ?? $logData[$k] ?? null;
                     if (empty($d)) return '-';
                     if ($d instanceof \Carbon\Carbon) return $d->format('Y-m-d');
                     return substr((string)$d, 0, 10);
                 };
                 
                 // IBOX System Details
                 if ($item->code === 'ibox_condition') {
                     $b = $getV('ibox_battery_percent');
                     $attr[] = ['Battery', $b ? $b.' %' : '-'];
                     
                     $p = $getV('ibox_pressure');
                     $attr[] = ['Pressure', $p ? $p.' Bar' : '-'];
                     
                     $t1 = $getV('ibox_temperature_1');
                     $t2 = $getV('ibox_temperature_2');
                     $genT = $getV('ibox_temperature');
                     
                     $attr[] = ['Temperature #1', $t1 ? $t1.' °C' : ($genT ? $genT.' °C' : '-')];
                     $attr[] = ['Temperature #2', $t2 ? $t2.' °C' : '-'];
                     
                     $l = $getV('ibox_level');
                     $attr[] = ['Level', $l ? $l.' %' : '-'];
                 }
                 
                 // Pressure Gauge Details
                 if ($item->code === 'pressure_gauge_condition') {
                     $attr[] = ['Serial Number', $getV('pressure_gauge_serial_number') ?? '-'];
                     $attr[] = ['Calibration Date', $getD('pressure_gauge_calibration_date')];
                     
                     $p1 = $getV('pressure_1');
                     $attr[] = ['Reading (Pressure 1)', $p1 ? $p1.' MPa' : '-'];
                     
                     $p2 = $getV('pressure_2');
                     $attr[] = ['Reading (Pressure 2)', $p2 ? $p2.' MPa' : '-'];
                 }
                 
                 // Level Gauge Details
                 if ($item->code === 'level_gauge_condition') {
                     $cond = $getV('level_gauge_condition');
                     $attr[] = ['Condition', $cond ? strtoupper($cond) : '-']; // Explicit "Condition" label
                     
                     $l1 = $getV('level_1');
                     $attr[] = ['Reading (Level 1)', $l1 ? $l1.' mm' : '-'];
                     
                     $l2 = $getV('level_2');
                     $attr[] = ['Reading (Level 2)', $l2 ? $l2.' mm' : '-'];
                 }
                 
                 // Vacuum System
                 if ($item->code === 'vacuum_gauge_condition') {
                      $val = $getV('vacuum_value');
                      if ($val !== null && is_numeric($val)) {
                          $val = (float)$val; // Removes trailing zeros (e.g. 1.2000 -> 1.2)
                      }
                      $unit = $getV('vacuum_unit') ?? 'mTorr';
                      $attr[] = ['Vacuum Value', $val !== null ? "$val $unit" : '-'];
                      
                      $vt = $getV('vacuum_temperature');
                      $attr[] = ['Vacuum Temp', $vt ? $vt.' °C' : '-'];
                      
                      // Check Date Time
                      $vcd = $getV('vacuum_check_datetime');
                      $vcdDisplay = '-';
                      if ($vcd) {
                          if ($vcd instanceof \Carbon\Carbon) $vcdDisplay = $vcd->format('Y-m-d H:i');
                          else $vcdDisplay = substr((string)$vcd, 0, 16);
                      }
                      $attr[] = ['Check Datetime', $vcdDisplay];
                 }

                 // PSV Details (1-4)
                 if (Str::startsWith($item->code, 'psv') && Str::endsWith($item->code, '_condition')) {
                     $num = substr($item->code, 3, 1); 
                     $p = 'psv'.$num;
                     
                     $st = $getV($p.'_status');
                     $sn = $getV($p.'_serial_number');
                     
                     $statusStr = $st ? strtoupper($st) : '-';
                     $snStr = $sn ?? '-';
                     
                     // Combined Line 1
                     $attr[] = ['STATUS: ' . $statusStr . ' | SN: ' . $snStr, '']; 
                     
                     $cal = $getD($p.'_calibration_date');
                     $valid = $getD($p.'_valid_until');
                     
                     // Combined Line 2
                     $attr[] = ['Cal. Date: ' . $cal . ' | Valid Until: ' . $valid, ''];
                 }
                 
                 // Add attributes as "Pseudo Items"
                 foreach ($attr as $a) {
                     $finalConditions[] = [
                         'item_name' => $item->code . '_attr_' . Str::slug($a[0]),
                         'description' => $a[0],
                         'condition' => $a[1], 
                         'is_attribute' => true,
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
