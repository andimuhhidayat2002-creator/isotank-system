<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendWeeklyReport extends Command
{
    protected $signature = 'report:weekly {email? : Optional email override}';
    protected $description = 'Send weekly operations report email';

    public function handle()
    {
        $this->info('Generating Weekly Report...');

        // 1. DATE RANGE
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();
        
        // 2. ACTIVITY STATS (Throughput: Incoming Jobs + Confirmed Outgoing)
        $incomingWeek = \App\Models\InspectionJob::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'incoming_inspection')
            ->count();
            
        $outgoingWeek = \App\Models\InspectionLog::whereBetween('receiver_confirmed_at', [$startOfWeek, $endOfWeek])
            ->where('inspection_type', 'outgoing_inspection')
            ->count();

        $inspectionsWeek = $incomingWeek + $outgoingWeek;

        // Total YTD (Approximation consistent with logic)
        $incomingTotal = \App\Models\InspectionJob::where('activity_type', 'incoming_inspection')->count();
        $outgoingTotal = \App\Models\InspectionLog::whereNotNull('receiver_confirmed_at')->count();
        $inspectionsTotal = $incomingTotal + $outgoingTotal;

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
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $headers = ['ISO Number', 'Owner', 'Location', 'Status', 'Next Expiry Component', 'Expiry Date'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Style Header (Bold + Color)
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d47a1']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Data
        $row = 2;
        foreach(\App\Models\MasterIsotank::all() as $tank) {
            $earliest = $tank->components()->orderBy('expiry_date', 'asc')->first();
            
            // Format Component Name
            $compName = '-';
            if ($earliest) {
                if ($earliest->component_type === 'PG') {
                    $compName = 'Pressure Gauge';
                } else {
                    $compName = $earliest->component_type . ' ' . $earliest->position_code;
                }
            }
            
            $dataset = [
                $tank->iso_number,
                $tank->owner,
                $tank->location,
                $tank->filling_status_code,
                $compName,
                ($earliest && $earliest->expiry_date) ? $earliest->expiry_date->format('Y-m-d') : '-'
            ];
            
            $sheet->fromArray($dataset, NULL, 'A' . $row);
            $row++;
        }

        // Auto-Size Columns
        foreach(range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($excelPath);

        // 7. PREPARE EMAIL DATA
        $data = [
            'date_range' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
            'inspections_week' => $inspectionsWeek,
            'inspections_total' => $inspectionsTotal,
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
