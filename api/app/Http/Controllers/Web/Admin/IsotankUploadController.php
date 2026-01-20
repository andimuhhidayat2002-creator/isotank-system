<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\IsotankUpload;
use App\Imports\MasterIsotanksImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IsotankUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        // Store file
        $path = $file->store('isotank-uploads'); 

        $import = new MasterIsotanksImport;
        
        try {
            $import->import($file);
        } catch (\Exception $e) {
             return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }

        // Create Audit Record
        IsotankUpload::create([
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'uploaded_by' => auth()->id(),
            'total_rows' => $import->successCount + $import->errorCount,
            'success_count' => $import->successCount,
            'error_count' => $import->errorCount,
            'error_details' => $import->errorCount > 0 ? json_encode($import->errors) : null,
        ]);

        $msg = "Processed {$import->successCount} records successfully.";
        if ($import->errorCount > 0) {
            $msg .= " {$import->errorCount} errors found.";
            // Add Debug Info
            $firstError = $import->errors[0]['error'] ?? 'Unknown error';
            $detected = json_encode($import->detectedHeader);
            $msg .= " First Error: $firstError. Debug Header: $detected";
            
            return back()->with('warning', $msg);
        }

        return back()->with('success', $msg);
    }
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'ISO Number',
            'Owner', 
            'Manufacturer',
            'Model Type',
            'Serial Number',
            'Location',
            'Product',
            'Status',
            'Initial Pressure Test',
            'CSC Initial Test',
            'Class Survey Expiry',
            'CSC Survey Expiry'
        ];
        
        $sheet->fromArray([$headers], NULL, 'A1');

        // Example Row
        $example = [
            'HAIU1234567',
            'Kayan', 
            'CIMC', 
            'T11', 
            'SN12345', 
            'SMGRS', 
            'Latex', 
            'active',
            '2020-01-01', 
            '2020-01-01', 
            '2025-01-01', 
            '2025-01-01'
        ];
        $sheet->fromArray([$example], NULL, 'A2');

        // Styles
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        foreach(range('A','L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="isotank_master_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
