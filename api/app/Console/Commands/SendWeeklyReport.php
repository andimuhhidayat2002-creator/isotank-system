<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWeeklyReport extends Command
{
    protected $signature = 'report:weekly {email? : Optional email override}';
    protected $description = 'Send weekly operations report email';

    public function handle()
    {
        $this->info('Generating Weekly Operations Report...');

        // 1. DATE RANGE
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();

        // 2. ACTIVITY STATS (Throughput with Breakdown)
        // Incoming Breakdown
        $incomingStats = \App\Models\InspectionJob::whereBetween('inspection_jobs.created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'incoming_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // Outgoing Started Breakdown
        $outgoingStartedStats = \App\Models\InspectionJob::whereBetween('inspection_jobs.created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // Outgoing Official Breakdown
        $outgoingOfficialStats = \App\Models\InspectionLog::whereBetween('inspection_logs.receiver_confirmed_at', [$startOfWeek, $endOfWeek])
            ->where('inspection_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_logs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // Helper Closure
        $formatBreakdown = function($stats) {
            $parts = [];
            foreach($stats as $cat => $count) {
                $label = ($cat && $cat !== '') ? $cat : 'T75';
                if (isset($parts[$label])) $parts[$label] += $count;
                else $parts[$label] = $count;
            }
            $str = [];
            foreach($parts as $l => $c) $str[] = "$l: $c";
            return empty($str) ? '0' : implode(', ', $str);
        };

        $incomingWeek = $incomingStats->sum();
        $outgoingOfficialWeek = $outgoingOfficialStats->sum();
        $outgoingStartedWeek = $outgoingStartedStats->sum();
        $inspectionsWeek = $incomingWeek + $outgoingOfficialWeek;

        $incomingDesc = $incomingWeek . ' ' . ($incomingWeek > 0 ? '(' . $formatBreakdown($incomingStats) . ')' : '');
        $outgoingDesc = $outgoingOfficialWeek . ' (Official Out) ' . ($outgoingOfficialWeek > 0 ? '(' . $formatBreakdown($outgoingOfficialStats) . ')' : '');

        $maintenanceWeek = \App\Models\MaintenanceJob::whereBetween('completed_at', [$startOfWeek, $endOfWeek])->count();
        $maintenanceActive = \App\Models\MaintenanceJob::whereNull('completed_at')->count();

        // 3. FLEET STATUS BREAKDOWN
        $totalFleet = \App\Models\MasterIsotank::count();
        
        $statusRaw = \App\Models\MasterIsotank::select('filling_status_code', \DB::raw('count(*) as count'))
            ->groupBy('filling_status_code')
            ->orderBy('count', 'desc')
            ->get();

        $breakdownStatus = $statusRaw->map(function($item) {
            return [
                'code' => $item->filling_status_code ?: 'unknown',
                'count' => $item->count
            ];
        });

        // 4. LOCATION BREAKDOWN
        $locRaw = \App\Models\MasterIsotank::select('location', \DB::raw('count(*) as count'))
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->get();
            
        $breakdownLocation = $locRaw->map(function($item) {
            return [
                'name' => $item->location,
                'count' => $item->count
            ];
        });

        // 5. EXPIRY ALERTS (Next 30 Days)
        // Find tanks with components expiring soon
        $expiryLimit = now()->addDays(30);
        
        $alertTanks = \App\Models\MasterIsotank::with(['components' => function($q) use ($expiryLimit) {
            $q->where('expiry_date', '<=', $expiryLimit);
        }])->get()->filter(function($tank) {
             return $tank->components->isNotEmpty();
        });

        $expiryCount = $alertTanks->count();

        // 6. GENERATE EXCEL ATTACHMENT (.xlsx)
        $filename = 'weekly_report_' . time() . '_' . str()->random(5) . '.xlsx';
        $excelPath = storage_path('app/' . $filename);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // --- SHEET 1: SUMMARY ---
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Weekly Summary');
        
        // Title
        $summarySheet->setCellValue('A1', 'WEEKLY OPERATIONS SUMMARY');
        $summarySheet->mergeCells('A1:C1');
        $summarySheet->setCellValue('A2', 'Period: ' . $startOfWeek->format('d M Y') . ' - ' . $endOfWeek->format('d M Y'));
        $summarySheet->mergeCells('A2:C2');
        
        $headerStyleLocal = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ];
        $summarySheet->getStyle('A1')->applyFromArray($headerStyleLocal);
        
        // KPIs Section
        $row = 4;
        $summarySheet->setCellValue('A'.$row, 'KEY PERFORMANCE INDICATORS');
        $summarySheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        $summarySheet->setCellValue('A'.$row, 'Total Inspections (Incoming + Outgoing)')->setCellValue('B'.$row, $inspectionsWeek); $row++;
        $summarySheet->setCellValue('A'.$row, 'Maintenance Jobs Completed')->setCellValue('B'.$row, $maintenanceWeek); $row++;
        $summarySheet->setCellValue('A'.$row, 'Active Maintenance Jobs')->setCellValue('B'.$row, $maintenanceActive); $row++;
        $summarySheet->setCellValue('A'.$row, 'Total Fleet Size')->setCellValue('B'.$row, $totalFleet); $row++;
        $row++;

        // Filling Status Section
        $summarySheet->setCellValue('A'.$row, 'FILLING STATUS BREAKDOWN');
        $summarySheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        if ($breakdownStatus->isNotEmpty()) {
            foreach ($breakdownStatus as $stat) {
                // Ensure label is readable
                $label = $stat['code'] ? ucfirst(str_replace('_', ' ', $stat['code'])) : 'No Status';
                $summarySheet->setCellValue('A'.$row, $label);
                $summarySheet->setCellValue('B'.$row, $stat['count']);
                $row++;
            }
        } else {
             $summarySheet->setCellValue('A'.$row, 'No data available.'); $row++;
        }
        $row++;

        // Location Section
        $summarySheet->setCellValue('A'.$row, 'LOCATION DISTRIBUTION');
        $summarySheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;

        if ($breakdownLocation->isNotEmpty()) {
            foreach ($breakdownLocation as $loc) {
                $summarySheet->setCellValue('A'.$row, $loc['name']);
                $summarySheet->setCellValue('B'.$row, $loc['count']);
                $row++;
            }
        }
        
        $summarySheet->getColumnDimension('A')->setAutoSize(true);
        $summarySheet->getColumnDimension('B')->setAutoSize(true);


        // --- SHEET 2: FLEET LIST ---
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Fleet Status List');
        
        // Header
        $headers = ['ISO Number', 'Owner', 'Location', 'Status', 'Filling Status', 'Next Expiry Component', 'Expiry Date'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Style Header (Bold + Color)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d47a1']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle); // Adjusted range A1:G1
        
        // Data
        $row = 2;
        // Optimization: Use chunking if dataset is large, but for now filtering all() is acceptable for <1000 units
        $tanks = \App\Models\MasterIsotank::with(['components' => function($q) {
             $q->orderBy('expiry_date', 'asc');
        }])->get();

        foreach($tanks as $tank) {
            $earliest = $tank->components->first(); // Since we ordered in eager load
            
            // Format Component Name
            $compName = '-';
            if ($earliest) {
                if ($earliest->component_type === 'PG') {
                    $compName = 'Pressure Gauge';
                } else {
                    $compName = $earliest->component_type . ($earliest->position_code ? ' ' . $earliest->position_code : '');
                }
            }

            // Readable Filling Status
            $fillRaw = $tank->filling_status_code;
            $fillReadable = $fillRaw ? ucfirst(str_replace('_', ' ', $fillRaw)) : '-';
            
            $dataset = [
                $tank->iso_number,
                $tank->owner,
                $tank->location,
                $tank->status,
                $fillReadable, // New Column
                $compName,
                ($earliest && $earliest->expiry_date) ? $earliest->expiry_date->format('Y-m-d') : '-'
            ];
            
            $sheet->fromArray($dataset, NULL, 'A' . $row);
            $row++;
        }

        // Auto-Size Columns
        foreach(range('A','G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0); // Set Summary as first view
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($excelPath);

        // 7. PREPARE EMAIL DATA // Re-calculate Total YTD
        $incomingTotal = \App\Models\InspectionJob::where('activity_type', 'incoming_inspection')->count();
        $outgoingTotal = \App\Models\InspectionLog::whereNotNull('receiver_confirmed_at')->count();
        $inspectionsTotal = $incomingTotal + $outgoingTotal;

        $data = [
            'date_range' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
            'inspections_week' => $inspectionsWeek,
            'inspections_total' => $inspectionsTotal,
            'incoming_desc' => $incomingDesc,
            'outgoing_desc' => $outgoingDesc,
            'outgoing_started_desc' => $outgoingStartedWeek . ' (Process Started)',
            'maintenance_week' => $maintenanceWeek,
            'maintenance_active' => $maintenanceActive,
            'total_fleet' => $totalFleet,
            'breakdown_status' => $breakdownStatus,
            'breakdown_location' => $breakdownLocation,
            'expiry_alerts_count' => $expiryCount
        ];

        // 8. SEND EMAIL
        // Handle recipients
        if (!$this->argument('email')) {
             $recipients = \App\Models\User::where('role', 'admin')->pluck('email')->toArray();
        } else {
             // Split by comma if multiple emails are provided
             $rawInput = $this->argument('email');
             $recipients = array_map('trim', explode(',', $rawInput));
        }
        
        $this->info("Sending to " . count($recipients) . " recipients...");

        foreach($recipients as $email) {
            try {
                \Mail::to($email)->send(new \App\Mail\WeeklyOperationsReport($data, $excelPath));
                $this->info("Sent to: $email");
            } catch (\Throwable $e) {
                $this->error("Failed to send to $email: " . $e->getMessage());
                \Log::error("Weekly Report Mail Error: " . $e->getMessage());
            }
        }

        // Cleanup
        if (file_exists($excelPath)) {
            @unlink($excelPath);
        }

        $this->info('Weekly Report process completed.');
    }
}
