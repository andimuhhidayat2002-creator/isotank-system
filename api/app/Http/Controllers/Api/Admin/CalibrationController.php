<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterIsotank;
use App\Models\MasterIsotankComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalibrationController extends Controller
{
    /**
     * Get list of isotanks with calibration summary
     */
    public function index(Request $request)
    {
        $query = MasterIsotank::select('id', 'iso_number', 'location', 'status')
            ->withCount(['components as components_count'])
            ->with(['components' => function($q) {
                // Get earliest expiry date of active components
                $q->active()->select('isotank_id', 'expiry_date')
                  ->orderBy('expiry_date', 'asc');
            }]);

        if ($request->has('search')) {
            $query->where('iso_number', 'like', '%' . $request->search . '%');
        }

        $isotanks = $query->paginate(20);
        
        // Enhance data with summary status
        $isotanks->getCollection()->transform(function($tank) {
            $earliestExpiry = $tank->components->first()?->expiry_date;
            $tank->calibration_status = $earliestExpiry 
                ? ($earliestExpiry < now() ? 'expired' : 'valid') 
                : 'no_data';
            $tank->next_expiry = $earliestExpiry;
            unset($tank->components); // Cleanup
            return $tank;
        });

        return response()->json($isotanks);
    }

    /**
     * Get all components for a specific Isotank (for the Grid View)
     */
    public function show($id)
    {
        $isotank = MasterIsotank::with(['components' => function($q) {
            $q->orderBy('component_type')->orderBy('position_code');
        }])->findOrFail($id);

        return response()->json([
            'isotank' => [
                'id' => $isotank->id,
                'iso_number' => $isotank->iso_number,
                'location' => $isotank->location
            ],
            // Grouping for easy UI rendering
            'components' => $isotank->components->groupBy('component_type'), 
            'all_components' => $isotank->components // Flat list for easy binding
        ]);
    }

    /**
     * Initialize default components (1 PG, 4 PSV, 7 PRV)
     * This is the "One Click Setup"
     */
    public function initialize($id)
    {
        $isotank = MasterIsotank::findOrFail($id);

        if ($isotank->components()->count() > 0) {
            return response()->json(['message' => 'Components already initialized'], 400);
        }

        DB::transaction(function() use ($isotank) {
            // 1. Pressure Gauge (PG)
            $isotank->components()->create([
                'component_type' => 'PG',
                'position_code' => 'Main',
                'description' => 'Main Pressure Gauge'
            ]);

            // 2. Safety Valves (PSV 1-4)
            for ($i = 1; $i <= 4; $i++) {
                $isotank->components()->create([
                    'component_type' => 'PSV',
                    'position_code' => (string)$i,
                    'description' => "Safety Relief Valve #{$i}"
                ]);
            }

            // 3. Relief Valves (PRV 1-7) - External/Pipe
            for ($i = 1; $i <= 7; $i++) {
                $isotank->components()->create([
                    'component_type' => 'PRV',
                    'position_code' => (string)$i,
                    'description' => "Pipeline Relief Valve #{$i}"
                ]);
            }
        });

        return response()->json(['message' => 'Default components (PG, 4 PSV, 7 PRV) created successfully']);
    }

    /**
     * Bulk Update Components (The "Edit Borongan" Logic)
     */
    public function bulkUpdate(Request $request, $id)
    {
        $isotank = MasterIsotank::findOrFail($id);
        
        $data = $request->validate([
            'components' => 'required|array',
            'components.*.id' => 'required|exists:master_isotank_components,id',
            'components.*.serial_number' => 'nullable|string',
            'components.*.certificate_number' => 'nullable|string',
            'components.*.set_pressure' => 'nullable|numeric',
            'components.*.last_calibration_date' => 'nullable|date',
            'components.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function() use ($data) {
            foreach ($data['components'] as $item) {
                // Update specific component
                MasterIsotankComponent::where('id', $item['id'])->update([
                    'serial_number' => $item['serial_number'] ?? null,
                    'certificate_number' => $item['certificate_number'] ?? null,
                    'set_pressure' => $item['set_pressure'] ?? null,
                    'last_calibration_date' => $item['last_calibration_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }
        });

        return response()->json(['message' => 'Calibration data updated successfully']);
    }
}
