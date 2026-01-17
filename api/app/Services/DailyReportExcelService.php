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
        
        // Movement Summary
        $sheet->setCellValue('A4', 'MOVEMENT SUMMARY');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        $sheet->setCellValue('A5', 'Incoming Today')->setCellValue('B5', $summary['incoming']);
        $sheet->setCellValue('A6', 'Outgoing Today')->setCellValue('B6', $summary['outgoing']);
        $sheet->setCellValue('A7', 'Stock at Site')->setCellValue('B7', $summary['stock_site']);
        $sheet->setCellValue('A8', 'Stock Other Locations')->setCellValue('B8', $summary['stock_other']);

        // Exceptions
        $row = 11;
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

        $headers = ['Time', 'ISO Number', 'Type', 'Status', 'Inspector', 'Certificate/Doc'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue('A' . $row, $log->created_at->format('H:i'));
            $sheet->setCellValue('B' . $row, $log->isotank->iso_number);
            $sheet->setCellValue('C' . $row, $log->inspection_type);
            $sheet->setCellValue('D' . $row, $log->filling_status_code ?? '-');
            $sheet->setCellValue('E' . $row, $log->inspector->name ?? '-');
            $sheet->setCellValue('F' . $row, $log->doc_number ?? '-');
            $row++;
        }

        foreach(range('A','F') as $col) {
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

        $headers = ['ISO Number', 'Item', 'Description', 'Technician'];
        $sheet->fromArray($headers, NULL, 'A2');
        $row = 3;

        foreach ($maintenance['completed'] as $job) {
            $sheet->setCellValue('A' . $row, $job->isotank->iso_number);
            $sheet->setCellValue('B' . $row, $job->source_item);
            $sheet->setCellValue('C' . $row, $job->description);
            $sheet->setCellValue('D' . $row, $job->completedBy->name ?? '-');
            $row++;
        }

        // Outstanding
        $row += 3;
        $sheet->setCellValue('A'.$row, 'OUTSTANDING JOBS (>3 Days)');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        $headers = ['ISO Number', 'Pending Since', 'Days Open', 'Status'];
        $sheet->fromArray($headers, NULL, 'A'.$row);
        $row++;

        foreach ($maintenance['outstanding'] as $job) {
            $sheet->setCellValue('A' . $row, $job->isotank->iso_number);
            $sheet->setCellValue('B' . $row, $job->created_at->format('Y-m-d'));
            $sheet->setCellValue('C' . $row, $job->created_at->diffInDays(now()));
            $sheet->setCellValue('D' . $row, $job->status);
            $row++;
        }

        foreach(range('A','D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
