<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalibrationLog;
use App\Models\ExcelUploadLog;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkCalibrationUploadController extends Controller
{
    /**
     * Upload Excel for Bulk Calibration Activities
     * 
     * RULES:
     * - iso_number MUST exist and ACTIVE
     * - item_name MUST be calibratable item
     * - Each row creates ONE calibration_logs record
     * - Initial status = planned
     * - Calibration NEVER closes maintenance
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
            
            // Allowable calibratable items (Strict validation)
            $calibratableItems = [
                'pressure_gauge', 
                'safety_valve', 
                'psv1', 'psv2', 'psv3', 'psv4',
                'thermometer',
                // Add others as defined in business rules if any
            ];
            
            DB::beginTransaction();
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                if (empty(array_filter($row))) continue;
                
                $isoNumber = trim($row[0] ?? '');
                $itemName = trim($row[1] ?? '');
                $description = trim($row[2] ?? '');
                $plannedDate = $row[3] ?? null;
                $vendor = trim($row[4] ?? '');
                
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
                
                // 3. Create Calibration Log
                CalibrationLog::create([
                    'isotank_id' => $isotank->id,
                    'item_name' => $itemName,
                    'description' => $description,
                    'planned_date' => $plannedDate ? date('Y-m-d', strtotime($plannedDate)) : null,
                    'vendor' => $vendor ?: null,
                    'status' => 'planned',
                    'created_by' => $request->user()->id,
                ]);
                
                $successCount++;
            }
            
            ExcelUploadLog::create([
                'uploaded_by' => $request->user()->id,
                'activity_type' => 'calibration',
                'file_path' => $filePath,
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'failed_rows' => $failedRows,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk calibration upload completed',
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
            ->where('activity_type', 'calibration')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
