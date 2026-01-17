<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\YardSlot;
use App\Services\YardLayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\IsotankPosition;
use App\Models\MasterIsotank;

class YardLayoutController extends Controller
{
    protected $yardLayoutService;

    public function __construct(YardLayoutService $yardLayoutService)
    {
        $this->yardLayoutService = $yardLayoutService;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('excel_file');
            $path = $file->getPathname();

            $result = $this->yardLayoutService->importFromExcel($path);

            return response()->json([
                'success' => true,
                'message' => 'Yard layout updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Yard Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process yard layout: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Return active slots for rendering
        // Frontend will grid them by row/col
        // Include bg_color for visualization
        $slots = YardSlot::where('is_active', true)
            ->get(['id', 'row_index', 'col_index', 'area_label', 'bg_color']);

        return response()->json([
            'success' => true,
            'data' => $slots
        ]);
    }

    public function positions()
    {
        // Copy logic from Web YardController->getPositions
        // Get positions with isotanks
        // Eager load active jobs to determine activity
        $positions = IsotankPosition::with([
            'isotank', 
            'isotank.inspectionJobs' => function($q) {
                $q->whereIn('status', ['open', 'in_progress'])->orderBy('id', 'desc');
            },
            'isotank.calibrationLogs' => function($q) {
                $q->where('status', '!=', 'done')->orderBy('id', 'desc');
            },
            'slot'
        ])->get();

        $validPositions = collect();

        foreach ($positions as $pos) {
            if (!$pos->isotank || !$pos->slot) continue;
            // Check Isotank Validity
            if ($pos->isotank->status !== 'active') {
                continue;
            }
            $validPositions->push($pos);
        }
        
        // Transform valid positions
        $mappedPositions = $validPositions->map(function($pos) {
             // Determine Activity
             $activity = 'STORAGE'; // Default
             $ins = $pos->isotank->inspectionJobs->first();
             $cal = $pos->isotank->calibrationLogs->first();

             if ($ins) {
                 if (str_contains($ins->activity_type, 'incoming')) $activity = 'INCOMING';
                 elseif (str_contains($ins->activity_type, 'outgoing')) $activity = 'OUT GOING';
             } elseif ($cal) {
                 $activity = 'CALIBRATION';
             }

             return [
                'id' => $pos->id,
                'slot_id' => $pos->slot_id,
                'row_index' => $pos->slot->row_index,
                'col_index' => $pos->slot->col_index,
                'isotank' => [
                    'id' => $pos->isotank->id,
                    'isotank_number' => $pos->isotank->iso_number,
                    'current_cargo' => $pos->isotank->product ?? 'Unknown',
                    'filling_status' => $pos->isotank->filling_status_desc ?? 'Empty',
                    'filling_status_code' => $pos->isotank->filling_status_code,
                    'status' => $pos->isotank->status,
                    'activity' => $activity
                ]
            ];
        });

        // Get Unplaced Isotanks (SMGRS only for now)
        // Finding tanks in SMGRS that are NOT in positions
        $placedIds = $positions->pluck('isotank_id')->toArray();

        $unplaced = MasterIsotank::where('location', 'SMGRS')
            ->where('status', 'active')
            ->whereNotIn('id', $placedIds)
            ->get()
            ->map(function($tank) {
                return [
                    'id' => $tank->id,
                    'isotank_number' => $tank->iso_number,
                    'current_cargo' => $tank->product ?? 'Unknown',
                    'filling_status' => $tank->filling_status_desc ?? 'Empty',
                    'filling_status_code' => $tank->filling_status_code,
                    'status' => $tank->status,
                    'location' => $tank->location,
                    'activity' => 'STORAGE'
                ];
            });

        // Calculate Statistics
        $stats = [];
        
        // Area-based stats
        $allSlots = YardSlot::where('is_active', true)->get();
        $areaGroups = $allSlots->groupBy('area_label');
        
        foreach ($areaGroups as $area => $slots) {
            $occupied = $mappedPositions->filter(function($pos) use ($slots) {
                return $slots->pluck('id')->contains($pos['slot_id']);
            })->count();
            
            $stats[$area] = [
                'total' => $slots->count(),
                'occupied' => $occupied,
                'empty' => $slots->count() - $occupied
            ];
        }
        
        // Filling Status stats
        $allIsotanks = MasterIsotank::where('location', 'SMGRS')
            ->where('status', 'active')
            ->get();
            
        $fillingStatusStats = [];
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $count = $allIsotanks->where('filling_status_code', $code)->count();
            if ($count > 0) {
                $fillingStatusStats[$code] = [
                    'description' => $description,
                    'count' => $count
                ];
            }
        }
        
        // No status count
        $noStatusCount = $allIsotanks->whereNull('filling_status_code')->count();
        if ($noStatusCount > 0) {
            $fillingStatusStats['no_status'] = [
                'description' => 'No Status',
                'count' => $noStatusCount
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'placed' => $mappedPositions,
                'unplaced' => $unplaced,
                'stats' => $stats,
                'filling_status_stats' => $fillingStatusStats
            ]
        ]);
    }
}
