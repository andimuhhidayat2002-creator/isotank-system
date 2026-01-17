<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExcelUploadLog;
use App\Models\InspectionJob;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkInspectionUploadController extends Controller
{
    /**
     * Upload Excel for Bulk Inspection Jobs
     * 
     * CRITICAL RULES:
     * - Admin selects activity_type BEFORE upload
     * - Excel MUST NOT define activity_type
     * - Inactive isotanks MUST be rejected
     * - Duplicate iso_number rows are ALLOWED
     * - Each row creates ONE inspection_jobs record
     * 
     * SPECIAL LOCATION RULE (LOCKED):
     * - When Admin creates INCOMING inspection job:
     *   → master_isotanks.location MUST be FORCE-UPDATED to "SMGRS"
     *   → Regardless of previous location
     *   → Backend logic ONLY
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'activity_type' => 'required|in:incoming_inspection,outgoing_inspection',
        ]);

        $activityType = $request->activity_type;
        $file = $request->file('file');
        
        // Store uploaded file for audit
        $filePath = $file->store('excel_uploads', 'public');
        
        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            $header = array_shift($rows);
            
            $successCount = 0;
            $failedCount = 0;
            $failedRows = [];
            
            DB::beginTransaction();
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because index starts at 0 and we removed header
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $isoNumber = trim($row[0] ?? '');
                $plannedDate = $row[1] ?? null;
                $destination = $row[2] ?? null;
                $receiverName = $row[3] ?? null; // NEW: Receiver name from column 4
                
                // Validation
                if (empty($isoNumber)) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'iso_number is mandatory',
                    ];
                    continue;
                }
                
                // Find isotank
                $isotank = MasterIsotank::where('iso_number', $isoNumber)->first();
                
                if (!$isotank) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'iso_number not found in master_isotanks',
                    ];
                    continue;
                }
                
                // Check if isotank is active
                if ($isotank->status !== 'active') {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'Isotank is inactive',
                    ];
                    continue;
                }
                
                // Validate destination for outgoing
                if ($activityType === 'outgoing_inspection' && empty($destination)) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'destination is required for outgoing inspection',
                    ];
                    continue;
                }
                
                // Validate receiver_name for outgoing (EXTENSION REQUIREMENT)
                if ($activityType === 'outgoing_inspection' && empty($receiverName)) {
                    $failedCount++;
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'iso_number' => $isoNumber,
                        'reason' => 'receiver_name is required for outgoing inspection',
                    ];
                    continue;
                }
                
                // Create inspection job
                InspectionJob::create([
                    'isotank_id' => $isotank->id,
                    'activity_type' => $activityType,
                    'planned_date' => $plannedDate ? date('Y-m-d', strtotime($plannedDate)) : null,
                    'destination' => $activityType === 'outgoing_inspection' ? $destination : null,
                    'receiver_name' => $activityType === 'outgoing_inspection' ? $receiverName : null,
                    'status' => 'open',
                ]);
                
                // SPECIAL LOCATION RULE (CRITICAL & LOCKED)
                // When Admin creates INCOMING inspection job:
                // → master_isotanks.location MUST be FORCE-UPDATED to "SMGRS"
                if ($activityType === 'incoming_inspection') {
                    $isotank->update(['location' => 'SMGRS']);
                }
                
                $successCount++;
            }
            
            // Log the upload
            ExcelUploadLog::create([
                'uploaded_by' => $request->user()->id,
                'activity_type' => $activityType,
                'file_path' => $filePath,
                'total_rows' => count($rows),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'failed_rows' => $failedRows,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk upload completed',
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
    
    /**
     * Get upload history
     */
    public function history()
    {
        $logs = ExcelUploadLog::with('uploader')
            ->whereIn('activity_type', ['incoming_inspection', 'outgoing_inspection'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
