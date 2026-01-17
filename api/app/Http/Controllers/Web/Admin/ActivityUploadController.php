<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityUpload;
use App\Imports\InspectionImport;
use App\Imports\MaintenanceImport;
use App\Imports\CalibrationImport;
use App\Imports\VacuumImport;
use Illuminate\Http\Request;

class ActivityUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'activity_type' => 'required|in:incoming_inspection,outgoing_inspection,maintenance,calibration,vacuum',
        ]);

        $file = $request->file('file');
        $type = $request->activity_type;
        $path = $file->store('activity-uploads');

        $import = null;

        switch ($type) {
            case 'incoming_inspection':
            case 'outgoing_inspection':
                $import = new InspectionImport($type);
                break;
            case 'maintenance':
                $import = new MaintenanceImport();
                break;
            case 'calibration':
                $import = new CalibrationImport();
                break;
            case 'vacuum':
                $import = new VacuumImport();
                break;
        }

        try {
            if ($import) {
                $import->import($file);
            }
        } catch (\Exception $e) {
             return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }

        ActivityUpload::create([
            'activity_type' => $type,
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'uploaded_by' => auth()->id(),
            'total_rows' => $import->successCount + $import->errorCount,
            'success_count' => $import->successCount,
            'error_count' => $import->errorCount,
            'error_details' => $import->errorCount > 0 ? json_encode($import->errors) : null,
        ]);

        $msg = "Processed {$import->successCount} {$type} activities.";
        if ($import->errorCount > 0) {
            $msg .= " {$import->errorCount} errors found (check log).";
            return back()->with('warning', $msg);
        }

        return back()->with('success', $msg);
    }
}
