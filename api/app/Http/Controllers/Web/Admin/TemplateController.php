<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateController extends Controller
{
    public function downloadCalibrationTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        // Row 1: Headers
        $headers = [
            'ISO Number', 
            'Planned Date', 
            'Vendor', 
            'Description', 
            'SN Pressure Gauge', 
            'SN PSV 1', 
            'SN PSV 2', 
            'SN PSV 3', 
            'SN PSV 4'
        ];
        
        $sheet->fromArray([$headers], NULL, 'A1');

        // Example Row
        $example = [
            'ISO123456',
            date('Y-m-d'),
            'Test Vendor',
            'Annual Calibration',
            'SN-PG-001',
            'SN-PSV-101',
            '', // Empty means skip
            '',
            ''
        ];
        $sheet->fromArray([$example], NULL, 'A2');

        // Styles
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        foreach(range('A','I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="calibration_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function downloadInspectionTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers for Inspection
        // Unified template for both Incoming and Outgoing
        
        $headers = [
            'ISO Number', 
            'Planned Date', 
            'Filling Status Code', // e.g., ready_to_fill, filled
            'Filling Status Description', 
            'Destination', // Outgoing only
            'Receiver Name' // Outgoing only
        ];
        
        $sheet->fromArray([$headers], NULL, 'A1');

        // Example Row
        $example = [
            'ISO123456',
            date('Y-m-d'),
            'ready_to_fill', // filling_status_code
            'Clean and Ready', // filling_status_desc
            'Jakarta (Outgoing Only)',
            'PT Receiver (Outgoing Only)'
        ];
        $sheet->fromArray([$example], NULL, 'A2');

        // Add a legend/help sheet or just comments? 
        // Let's add a comment on the header for Filling Status Code
        $sheet->getComment('C1')->getText()->createTextRun("Valid Codes:\nongoing_inspection\nready_to_fill\nfilled\nunder_maintenance\nwaiting_team_calibration\nclass_survey");

        // Styles
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        foreach(range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="inspection_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function downloadMaintenanceTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers for Maintenance
        // iso_number, item_name, description, priority, planned_date, status, completion_date, work_description
        $headers = [
            'ISO Number', 
            'Item Name', 
            'Description', 
            'Priority', 
            'Planned Date',
            'Part Damage',
            'Damage Type',
            'Location'
        ];
        
        $sheet->fromArray([$headers], NULL, 'A1');

        // Example Row
        $example = [
            'ISO123456',
            'Bottom Valve',
            'Leak repair',
            'high', // low, normal, high, urgent
            date('Y-m-d'),
            'Crack',
            'Major',
            'Left Side'
        ];
        $sheet->fromArray([$example], NULL, 'A2');

        // Styles
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach(range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="maintenance_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0'); // no cache

        return $response;
    }

    public function downloadVacuumTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers for Vacuum
        // iso_number, check_date, value, unit, temperature, remarks
        $headers = [
            'ISO Number', 
            'Check Date', 
            'Value', 
            'Unit', 
            'Temperature',
            'Remarks'
        ];
        
        $sheet->fromArray([$headers], NULL, 'A1');

        // Example Row
        $example = [
            'ISO123456',
            date('Y-m-d H:i:s'),
            '12.5',
            'mTorr',
            '25.5',
            'Historical Data'
        ];
        $sheet->fromArray([$example], NULL, 'A2');

        // Styles
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        foreach(range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="vacuum_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
