<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyReportExcelService
{
    public function generate($date, $data)
    {
        $spreadsheet = new Spreadsheet();
        
        // Remove default sheet
        $spreadsheet->removeSheetByIndex(0);

        // 1. Summary Sheet
        $this->createSummarySheet($spreadsheet, $date, $data['summary'], $data['issues']);

        // 2. Inspection Sheet
        $this->createInspectionSheet($spreadsheet, $data['inspectionLogs']);

        // 3. Maintenance Sheet
        $this->createMaintenanceSheet($spreadsheet, $data['maintenance']);

        // 4. Calibration Sheet
        $this->createCalibrationSheet($spreadsheet, $data['calibrationItems']);

        // Create file in memory
        $writer = new Xlsx($spreadsheet);
        
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function createSummarySheet($spreadsheet, $date, $summary, $issues)
    {
        $sheet = new Worksheet($spreadsheet, 'Summary');
        $spreadsheet->addSheet($sheet, 0);

        // Header
        $sheet->setCellValue('A1', 'Daily Operations Report');
        $sheet->setCellValue('A2', 'Date: ' . $date);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // KPIs
        $sheet->setCellValue('A4', 'OPERATIONAL KPIs');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->setCellValue('A5', 'Inspections Submitted Today')->setCellValue('B5', $summary['inspections_today']);
        $sheet->setCellValue('A6', 'Active Maintenance Jobs (Total)')->setCellValue('B6', $summary['open_maintenance']);
        $sheet->setCellValue('A7', 'Calibration Compliance Progress')->setCellValue('B7', $summary['calibration_progress'] . '%');

        // Filling Status Summary (New)
        $sheet->setCellValue('A9', 'FILLING STATUS SUMMARY');
        $sheet->getStyle('A9')->getFont()->setBold(true);
        
        $row = 10;
        if (!empty($summary['filling_status_breakdown'])) {
            foreach ($summary['filling_status_breakdown'] as $status => $count) {
                $sheet->setCellValue('A' . $row, $status);
                $sheet->setCellValue('B' . $row, $count);
                $row++;
            }
        } else {
            $sheet->setCellValue('A' . $row, 'No active status data available.');
            $row++;
        }
        $row++; // Spacer

        // Movement Summary
        $sheet->setCellValue('A'.$row, 'MOVEMENT SUMMARY');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Incoming Today (Gate In)')->setCellValue('B'.$row, $summary['incoming']); $row++;
        $sheet->setCellValue('A'.$row, 'Outgoing Process Started (Gate Out Start)')->setCellValue('B'.$row, $summary['outgoing_started']); $row++;
        $sheet->setCellValue('A'.$row, 'Official Outgoing (Gate Out Completed)')->setCellValue('B'.$row, $summary['outgoing_official']); $row++;
        $sheet->setCellValue('A'.$row, 'Stock at Site')->setCellValue('B'.$row, $summary['stock_site']); $row++;
        $sheet->setCellValue('A'.$row, 'Stock Other Locations')->setCellValue('B'.$row, $summary['stock_other']); $row++;
        $row++; // Spacer

        // Exceptions
        $sheet->setCellValue('A'.$row, 'EXCEPTION REPORT (ISSUES)');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        $headers = ['ISO Number', 'Type', 'Issue Notes'];
        $sheet->fromArray($headers, NULL, 'A'.$row);
        $sheet->getStyle("A$row:C$row")->getFont()->setBold(true);
        $row++;

        if (empty($issues)) {
            $sheet->setCellValue('A'.$row, 'No critical issues today.');
        } else {
            foreach ($issues as $issue) {
                $sheet->setCellValue('A'.$row, $issue['iso_number']);
                $sheet->setCellValue('B'.$row, $issue['type']);
                $sheet->setCellValue('C'.$row, $issue['notes']);
                $row++;
            }
        }
        
        foreach(range('A','C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createInspectionSheet($spreadsheet, $logs)
    {
        $sheet = new Worksheet($spreadsheet, 'Inspections');
        $spreadsheet->addSheet($sheet);

        $headers = ['Time', 'ISO Number', 'Type', 'Inspector', 'Certificate/Doc'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log->created_at->format('H:i'));
            $sheet->setCellValue('B' . $row, $log->isotank->iso_number);
            $sheet->setCellValue('C' . $row, $log->inspection_type);
            $sheet->setCellValue('D' . $row, $log->inspector->name ?? '-');
            $sheet->setCellValue('E' . $row, $log->doc_number ?? '-');
            $row++;
        }

        foreach(range('A','E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createMaintenanceSheet($spreadsheet, $maintenance)
    {
        $sheet = new Worksheet($spreadsheet, 'Maintenance');
        $spreadsheet->addSheet($sheet);

        // Completed
        $sheet->setCellValue('A1', 'COMPLETED TODAY');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headers = ['ISO Number', 'Item', 'Description', 'Technician', 'Completed At'];
        $sheet->fromArray($headers, NULL, 'A2');
        $row = 3;

        foreach ($maintenance['completed'] as $job) {
            $sheet->setCellValue('A' . $row, $job->isotank->iso_number);
            $sheet->setCellValue('B' . $row, $job->source_item);
            $sheet->setCellValue('C' . $row, $job->description);
            $sheet->setCellValue('D' . $row, $job->completedBy->name ?? '-');
            $sheet->setCellValue('E' . $row, $job->updated_at->format('Y-m-d H:i'));
            $row++;
        }

        // Outstanding
        $row += 3;
        $sheet->setCellValue('A'.$row, 'ALL OUTSTANDING JOBS');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        $headers = ['ISO Number', 'Created At', 'Days Open', 'Status', 'Item'];
        $sheet->fromArray($headers, NULL, 'A'.$row);
        $row++;

        foreach ($maintenance['outstanding'] as $job) {
            $sheet->setCellValue('A' . $row, $job->isotank->iso_number);
            $sheet->setCellValue('B' . $row, $job->created_at->format('Y-m-d'));
            $sheet->setCellValue('C' . $row, $job->created_at->diffInDays(now()));
            $sheet->setCellValue('D' . $row, $job->status);
            $sheet->setCellValue('E' . $row, $job->source_item);
            $row++;
        }

        foreach(range('A','E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createCalibrationSheet($spreadsheet, $calItems)
    {
        $sheet = new Worksheet($spreadsheet, 'Calibration Activities');
        $spreadsheet->addSheet($sheet);

        $headers = ['ISO Number', 'Item Name', 'Serial Number', 'Planned Date', 'Vendor', 'Actual Cal. Date', 'Valid Until', 'Status'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($calItems as $item) {
            $sheet->setCellValue('A' . $row, $item->isotank->iso_number ?? '-');
            $sheet->setCellValue('B' . $row, $item->item_name);
            $sheet->setCellValue('C' . $row, $item->serial_number);
            $sheet->setCellValue('D' . $row, $item->planned_date ? $item->planned_date->format('Y-m-d') : '-');
            $sheet->setCellValue('E' . $row, $item->vendor ?? '-');
            $sheet->setCellValue('F' . $row, $item->calibration_date ? $item->calibration_date->format('Y-m-d') : '-');
            $sheet->setCellValue('G' . $row, $item->valid_until ? $item->valid_until->format('Y-m-d') : '-');
            $sheet->setCellValue('H' . $row, strtoupper($item->status));
            
            // Highlight completed
            if ($item->status === 'completed') {
                $sheet->getStyle("A$row:H$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
            }
            $row++;
        }

        foreach(range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
