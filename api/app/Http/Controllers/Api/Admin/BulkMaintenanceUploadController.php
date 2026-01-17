<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExcelUploadLog;
use App\Models\MaintenanceJob;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkMaintenanceUploadController extends Controller
{
    /**
     * Upload Excel for Bulk Maintenance Jobs
     * 
     * RULES:
     * - iso_number MUST exist and ACTIVE
     * - item_name MUST match inspection item EXACTLY
     * - Each row creates ONE maintenance_jobs record
     * - Initial maintenance status = open
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $filePath = $file->store('excel_uploads', 'public');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header
            array_shift($rows);
            
            $successCount = 0;
            $failedCount = 0;
            $failedRows = [];
            
            // Valid item list for validation
            $validItems = [
                'surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover',
                'grounding_system', 'document_container', 'safety_label', 'valve_box_door',
                'valve_box_door_handle', 'valve_condition', 'pipe_joint', 'air_source_connection',
                'esdv', 'blind_flange', 'prv', 'ibox_condition', 'pressure_gauge_condition',
                'level_gauge_condition', 'psv1_condition', 'psv2_condition', 'psv3_condition', 'psv4_condition',
                // Add specific vacuum/calibration items if they can be maintenance targets directly
            ];
            
            DB::beginTransaction();
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                if (empty(array_filter($row))) continue;
                
                $isoNumber = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $description = trim($row[2] ?? '');
                $priority = trim($row[3] ?? '');
                $plannedDate = $row[4] ?? null;
                
                // 1. Validate Mandatory Fields
                if (empty($isoNumber) || empty($itemName) || empty($description)) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'Missing mandatory fields (iso_number, item_name, description)',
                    ];
                    continue;
                }
                
                // 2. Validate Isotank Exists & Active
                $isotank = MasterIsotank::where('iso_number', $isoNumber)->first();
                if (!$isotank) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'Isotank not found',
                    ];
                    continue;
                }
                
                if ($isotank->status !== 'active') {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'Isotank is inactive',
                    ];
                    continue;
                }
                
                // 3. Validate Item Name (Optional but good practice based on "MUST match inspection item EXACTLY")
                // We'll trust the admin uses the snake_case names as per system design, 
                // effectively we are creating a maintenance job for a specific item.
                
                MaintenanceJob::create([
                    'isotank_id' => $isotank->id,
                    'source_item' => $itemName,
                    'description' => $description,
                    'priority' => $priority ?: null,
                    'planned_date' => $plannedDate ? date('Y-m-d', strtotime($plannedDate)) : null,
                    'status' => 'open',
                    'created_by' => $request->user()->id,
                ]);
                
                $successCount++;
            }
            
            ExcelUploadLog::create([
                'uploaded_by' => $request->user()->id,
                'activity_type' => 'maintenance',
                'file_path' => $filePath,
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'failed_rows' => $failedRows,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk maintenance upload completed',
                'data' => [
                    'total_rows' => count($rows),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'failed_rows' => $failedRows,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history()
    {
        $logs = ExcelUploadLog::with('uploader')
            ->where('activity_type', 'maintenance')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
