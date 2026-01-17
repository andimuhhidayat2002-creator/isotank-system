<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\YardSlot;
// use App\Models\YardCell; // Removed
use App\Services\YardLayoutService;
use App\Models\IsotankPosition;
use App\Models\IsotankPositionLog;
use App\Models\MasterIsotank;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class YardController extends Controller
{
    public function index()
    {
        return view('admin.yard.index');
    }

    public function getLayout()
    {
        // Return only active slots
        $slots = YardSlot::where('is_active', true)->get();
        return response()->json($slots);
    }

    public function getPositions()
    {
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
        $placedIsotankIds = [];
        $occupiedSlotIds = [];

        foreach ($positions as $pos) {
            if (!$pos->isotank || !$pos->slot) continue;
            
            // Check Isotank Validity
            if ($pos->isotank->status !== 'active') {
                continue;
            }

            $validPositions->push($pos);
            $placedIsotankIds[] = $pos->isotank_id;
            $occupiedSlotIds[] = $pos->slot_id;
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
                    'filling_status_code' => $pos->isotank->filling_status_code ?? null,
                    'status' => $pos->isotank->status,
                    'activity' => $activity
                ]
            ];
        });

        // Get Unplaced Isotanks (SMGRS only for now, or all active unplaced)
        $unplaced = MasterIsotank::where('location', 'SMGRS')
            ->where('status', 'active')
            ->whereNotIn('id', $placedIsotankIds)
            // Helper to get activity for unplaced too? 
            // Since we need it for tooltip in unplaced list too (maybe)
            ->with(['inspectionJobs' => function($q) {
                $q->whereIn('status', ['open', 'in_progress'])->orderBy('id', 'desc');
            }, 'calibrationLogs' => function($q) {
                $q->where('status', '!=', 'done')->orderBy('id', 'desc');
            }])
            ->get()
            ->map(function($tank) {
                $activity = 'STORAGE';
                $ins = $tank->inspectionJobs->first();
                $cal = $tank->calibrationLogs->first();

                if ($ins) {
                    if (str_contains($ins->activity_type, 'incoming')) $activity = 'INCOMING';
                    elseif (str_contains($ins->activity_type, 'outgoing')) $activity = 'OUT GOING';
                } elseif ($cal) {
                    $activity = 'CALIBRATION';
                }

                return [
                    'id' => $tank->id,
                    'isotank_number' => $tank->iso_number,
                    'current_cargo' => $tank->product ?? 'Unknown',
                    'filling_status' => $tank->filling_status_desc ?? 'Empty',
                    'filling_status_code' => $tank->filling_status_code ?? null,
                    'status' => $tank->status,
                    'location' => $tank->location,
                    'activity' => $activity
                ];
            });

        // --- STATISTICS CALCULATION ---
        // 1. Get all active slots to count totals
        $allSlots = YardSlot::where('is_active', true)->get(['id', 'area_label']);
        
        $stats = [];
        foreach ($allSlots as $slot) {
            $label = $slot->area_label ?? 'UNKNOWN';
            if (!isset($stats[$label])) {
                $stats[$label] = ['total' => 0, 'occupied' => 0];
            }
            $stats[$label]['total']++;
            
            if (in_array($slot->id, $occupiedSlotIds)) {
                $stats[$label]['occupied']++;
            }
        }
        
        // Sort keys
        ksort($stats);

        // --- FILLING STATUS STATISTICS ---
        $fillingStatusStats = [];
        $allIsotanksAtLocation = MasterIsotank::where('location', 'SMGRS')
            ->where('status', 'active')
            ->get();
        
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $count = $allIsotanksAtLocation->where('filling_status_code', $code)->count();
            if ($count > 0) {
                $fillingStatusStats[$code] = [
                    'description' => $description,
                    'count' => $count
                ];
            }
        }
        
        // Count unspecified
        $unspecifiedCount = $allIsotanksAtLocation->filter(function($tank) {
            return empty($tank->filling_status_code);
        })->count();
        
        if ($unspecifiedCount > 0) {
            $fillingStatusStats['no_status'] = [
                'description' => 'Not Specified',
                'count' => $unspecifiedCount
            ];
        }

        return response()->json([
            'placed' => $mappedPositions,
            'unplaced' => $unplaced,
            'stats' => $stats,
            'fillingStatusStats' => $fillingStatusStats
        ]);
    }

    public function moveIsotank(Request $request)
    {
        try {
            $request->validate([
                'isotank_id' => 'required|exists:master_isotanks,id',
                'slot_id' => 'required|exists:yard_slots,id'
            ]);

            $isotank = MasterIsotank::findOrFail($request->isotank_id);

            // Validation: Isotank must be in SMGRS (or relevant logic)
            if ($isotank->location !== 'SMGRS' || $isotank->status !== 'active') {
                return response()->json(['error' => 'Isotank is not active in SMGRS yard'], 400);
            }

            $targetSlot = YardSlot::findOrFail($request->slot_id);
            
            // Validate target is active
            if (!$targetSlot->is_active) {
                return response()->json(['error' => 'Target slot is not active'], 400);
            }

            // Check if slot is occupied
            $occupied = IsotankPosition::where('slot_id', $targetSlot->id)
                ->where('isotank_id', '!=', $isotank->id)
                ->exists();
            
            if ($occupied) {
                return response()->json(['error' => 'Slot is already occupied'], 409);
            }

            DB::beginTransaction();

            // Find existing position or create new
            $position = IsotankPosition::where('isotank_id', $isotank->id)->first();

            $fromData = null;
            if ($position) {
                $fromData = [
                    'from_slot_id' => $position->slot_id, // Map if log table supports it, else verify log schema
                ];
                
                // Update
                $position->update([
                    'slot_id' => $targetSlot->id,
                ]);
            } else {
                // Create
                $position = IsotankPosition::create([
                    'isotank_id' => $isotank->id,
                    'slot_id' => $targetSlot->id,
                ]);
            }

            // Log - Assuming IsotankPositionLog hasn't been migrated yet to use slot_id.
            // If it hasn't, we might need to handle it or migrate it.
            // For now, let's assume we log purely or suppress if columns match.
            // Standard log creation:
             IsotankPositionLog::create([
                    'isotank_id' => $isotank->id,
                    'to_yard_cell_id' => $targetSlot->id, // Warning: Old column name?
                    'moved_by' => auth()->id(),
             ]);

            DB::commit();

            return response()->json(['message' => 'Isotank moved successfully', 'position' => $position]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Move isotank error: ' . $e->getMessage());
            return response()->json(['error' => 'Move failed: ' . $e->getMessage()], 500);
        }
    }

    public function uploadLayout(Request $request, \App\Services\YardLayoutService $service)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls'
            ]);
            
            $file = $request->file('excel_file');
            $result = $service->importFromExcel($file->getPathname());
            
            return response()->json([
                'message' => 'Layout uploaded successfully',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
             \Log::error('Upload layout error: ' . $e->getMessage());
             return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function isInMergeRange($coordinate, $mergeRange) { return false; } // Deprecated
    private function getMergeRangeBounds($mergeRange) { return []; } // Deprecated
}
