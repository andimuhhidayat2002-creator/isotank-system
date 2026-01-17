<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterIsotank;
use App\Models\MasterIsotankComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalibrationMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls'
        ]);

        try {
            $import = new \App\Imports\CalibrationMasterImport();
            $import->import($request->file('file')); // Calling directly, avoiding Facade

            $msg = "Processed {$import->successCount} rows.";
            if ($import->errorCount > 0) {
                $msg .= " {$import->errorCount} errors skipped.";
                return back()->with('warning', $msg);
            }
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            return back()->with('error', 'Import Failed: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MasterIsotank::select('id', 'iso_number', 'location', 'status')
            ->withCount(['components as components_count'])
            ->with(['components' => function($q) {
                $q->active()->select('isotank_id', 'expiry_date')
                  ->orderBy('expiry_date', 'asc');
            }]);

        if ($request->has('search')) {
            $query->where('iso_number', 'like', '%' . $request->search . '%');
        }

        $isotanks = $query->paginate(20);

        // Transform for view
        $isotanks->getCollection()->transform(function($tank) {
            $earliestExpiry = $tank->components->first()?->expiry_date;
            $tank->calibration_status = $earliestExpiry 
                ? ($earliestExpiry < now() ? 'expired' : 'valid') 
                : 'no_data';
            $tank->next_expiry = $earliestExpiry;
            return $tank;
        });

        return view('admin.calibration-master.index', compact('isotanks'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $isotank = MasterIsotank::with(['components' => function($q) {
            $q->orderBy('component_type')->orderBy('position_code');
        }])->findOrFail($id);

        return view('admin.calibration-master.show', compact('isotank'));
    }

    /**
     * Helper to initialize via Web (redirect back)
     */
    public function initialize($id)
    {
        // Re-using logic or calling simple create
        $isotank = MasterIsotank::findOrFail($id);
        if ($isotank->components()->count() > 0) {
            return back()->with('error', 'Components already inititalized');
        }

        DB::transaction(function() use ($isotank) {
             // 1. PG
             $isotank->components()->create(['component_type' => 'PG', 'position_code' => 'Main', 'description' => 'Main Pressure Gauge']);
             // 2. PSV
             for ($i = 1; $i <= 4; $i++) $isotank->components()->create(['component_type' => 'PSV', 'position_code' => "$i", 'description' => "Safety Relief Valve #$i"]);
             // 3. PRV
             for ($i = 1; $i <= 7; $i++) $isotank->components()->create(['component_type' => 'PRV', 'position_code' => "$i", 'description' => "Pipeline Relief Valve #$i"]);
        });

        return back()->with('success', 'Default components created.');
    }

    /**
     * Batch Update Components (Form Submission)
     */
    public function batchUpdate(Request $request, $id)
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

        return back()->with('success', 'Calibration data updated successfully.');
    }

    public function export()
    {
        $fileName = 'calibration_master_full.csv';
        
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Isotank Number', 'Location'];
        
        // Define standard structure
        $struct = [
            'PG' => ['Main'],
            'PSV' => [1, 2, 3, 4],
            'PRV' => [1, 2, 3, 4, 5, 6, 7]
        ];

        // Build Header
        foreach ($struct as $type => $positions) {
            foreach ($positions as $pos) {
                // Short codes for header
                $p = $type . ($pos === 'Main' ? '' : $pos); // e.g., PG, PSV1
                $columns[] = "$p SN";
                $columns[] = "$p Cert";
                if ($type !== 'PG') $columns[] = "$p Press";
                $columns[] = "$p Cal Date";
                $columns[] = "$p Exp";
            }
        }

        $callback = function() use ($columns, $struct) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 recognition
            fwrite($file, "\xEF\xBB\xBF");
            
            // Use SEMICOLON (;) as delimiter which is safer for Excel in many regions
            fputcsv($file, $columns, ';');

            MasterIsotank::with('components')
                ->chunk(100, function($isotanks) use ($file, $struct) {
                    foreach ($isotanks as $tank) {
                        $row = [
                            $tank->iso_number,
                            $tank->location
                        ];
                        
                        // Map components by type_pos
                        $comps = [];
                        foreach ($tank->components as $c) {
                            $key = $c->component_type . '_' . $c->position_code;
                            $comps[$key] = $c;
                        }

                        foreach ($struct as $type => $positions) {
                            foreach ($positions as $pos) {
                                $key = $type . '_' . $pos;
                                $c = $comps[$key] ?? null;
                                
                                $row[] = $c ? $c->serial_number : '';
                                $row[] = $c ? $c->certificate_number : '';
                                if ($type !== 'PG') $row[] = $c ? $c->set_pressure : '';
                                $row[] = ($c && $c->last_calibration_date) ? $c->last_calibration_date->format('Y-m-d') : '';
                                $row[] = ($c && $c->expiry_date) ? $c->expiry_date->format('Y-m-d') : '';
                            }
                        }
                        fputcsv($file, $row, ';');
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
