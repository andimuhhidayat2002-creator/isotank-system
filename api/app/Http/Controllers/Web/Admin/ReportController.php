<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InspectionLog;
use App\Models\MasterIsotank;
use App\Models\MaintenanceJob;
use App\Mail\DailyOperationsReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\InspectionJob;

class ReportController extends Controller
{
    public function index()
    {
        // 1. DATE RANGE (Current Week Snapshot)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();
        
        $stats = [
            'inspections_week' => \App\Models\InspectionJob::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count(),
            'inspections_total' => \App\Models\InspectionJob::count(),
            'maintenance_week' => \App\Models\MaintenanceJob::whereBetween('completed_at', [$startOfWeek, $endOfWeek])->count(),
            'maintenance_active' => \App\Models\MaintenanceJob::whereNull('completed_at')->count(),
            'total_fleet' => \App\Models\MasterIsotank::count(),
        ];

        // Status Breakdown
        $statusRaw = \App\Models\MasterIsotank::select('filling_status_code', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('filling_status_code')
            ->orderBy('count', 'desc')
            ->get();
            
        $stats['breakdown_status'] = $statusRaw->map(function($item) {
            return [
                'code' => $item->filling_status_code ?: 'unknown', 
                'count' => $item->count,
                'label' => $this->formatStatus($item->filling_status_code)
            ];
        });

        // Expiry Count
        $expiryLimit = now()->addDays(30);
        $alertTanks = \App\Models\MasterIsotank::with(['components' => function($q) use ($expiryLimit) {
            $q->where('expiry_date', '<=', $expiryLimit);
        }])->get()->filter(function($tank) {
             return $tank->components->isNotEmpty();
        });
        $stats['expiry_count'] = $alertTanks->count();

        return view('admin.reports.index', compact('stats'));
    }

    public function sendUnified(Request $request) 
    {
        $request->validate([
            'email' => 'required',
            'type' => 'required|in:daily,weekly',
            'date' => 'required|date'
        ]);

        $email = $request->email;
        $date = \Carbon\Carbon::parse($request->date);

        if ($request->type === 'weekly') {
            // Send Weekly
            try {
                \Illuminate\Support\Facades\Artisan::call('report:weekly', ['email' => $email]);
                return back()->with('success', "Weekly Report sent to $email (Background Process)");
            } catch (\Throwable $e) {
                return back()->with('error', 'Error sending Weekly Report: ' . $e->getMessage());
            }
        } else {
            // Send Daily
            try {
                // Handle multiple emails
                $targetEmails = array_map('trim', explode(',', $email));
                $validEmails = array_filter($targetEmails, function($e) {
                    return filter_var($e, FILTER_VALIDATE_EMAIL);
                });

                if (empty($validEmails)) {
                    return back()->with('error', 'No valid email addresses provided.');
                }

                $data = $this->getDailyReportData($date);
                
                // Generate Excel using Service
                $excelService = app(\App\Services\DailyReportExcelService::class);
                $excelContent = $excelService->generate($data['dateFormatted'], $data);

                // Send Email
                Mail::to($validEmails)->send(new DailyOperationsReport(
                    $data['dateFormatted'], 
                    $data['summary'], 
                    $data['issues'], 
                    $data['inspectionLogs'], 
                    $data['maintenance'],
                    $excelContent
                ));
                
                // Cache emails
                Cache::put('daily_report_recipients', implode(', ', $validEmails), now()->addDays(30));

                return back()->with('success', "Daily Report sent to: " . implode(', ', $validEmails));
            } catch (\Throwable $e) {
                Log::error("Daily Report Error: " . $e->getMessage());
                return back()->with('error', 'Error sending Daily Report: ' . $e->getMessage());
            }
        }
    }

    public function previewWeekly()
    {
        // 1. DATE RANGE (Current Week Snapshot)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();
        
        // 2. ACTIVITY STATS (Throughput: Incoming Jobs + Confirmed Outgoing)
        $incomingWeek = \App\Models\InspectionJob::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'incoming_inspection')
            ->count();
            
        $outgoingWeek = \App\Models\InspectionLog::whereBetween('receiver_confirmed_at', [$startOfWeek, $endOfWeek])
            ->where('inspection_type', 'outgoing_inspection')
            ->count();

        // Total YTD (Approximation)
        // Note: Counting ALL inspection jobs created (Incoming) + ALL confirmed outgoing logs might be heavy but accurate to logic.
        $incomingTotal = \App\Models\InspectionJob::where('activity_type', 'incoming_inspection')->count();
        $outgoingTotal = \App\Models\InspectionLog::whereNotNull('receiver_confirmed_at')->count();
        
        $stats = [
            'date_range' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
            'inspections_week' => $incomingWeek + $outgoingWeek,
            'inspections_total' => $incomingTotal + $outgoingTotal,
            'maintenance_week' => \App\Models\MaintenanceJob::whereBetween('completed_at', [$startOfWeek, $endOfWeek])->count(),
            'maintenance_active' => \App\Models\MaintenanceJob::whereNull('completed_at')->count(),
            'total_fleet' => \App\Models\MasterIsotank::count(),
        ];

        // Status Breakdown
        $statusRaw = \App\Models\MasterIsotank::select('filling_status_code', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('filling_status_code')
            ->orderBy('count', 'desc')
            ->get();
            
        $stats['breakdown_status'] = $statusRaw->map(function($item) {
            return [
                'code' => $item->filling_status_code ?: 'unknown', 
                'count' => $item->count,
                // 'label' => $this->formatStatus($item->filling_status_code) // Not needed for email view as it handles labels
            ];
        });
        
        $locRaw = \App\Models\MasterIsotank::select('location', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->get();
        $stats['breakdown_location'] = $locRaw->map(function($item) {
            return ['name' => $item->location, 'count' => $item->count];
        });

        // Expiry Count
        $expiryLimit = now()->addDays(30);
        $alertTanks = \App\Models\MasterIsotank::with(['components' => function($q) use ($expiryLimit) {
            $q->where('expiry_date', '<=', $expiryLimit);
        }])->get()->filter(function($tank) {
             return $tank->components->isNotEmpty();
        });
        $stats['expiry_alerts_count'] = $alertTanks->count();

        // Render Email View directly
        return view('emails.reports.weekly', $stats);
    }
    
    private function getDailyReportData($date) {
        $dateFormatted = $date->format('l, d F Y');

        // 1. Movement Summary
        // INCOMING: Count Jobs created (Admin "Gate In" action)
        $incoming = InspectionJob::whereDate('created_at', $date)
            ->where('activity_type', 'incoming_inspection')
            ->count();
        
        // OUTGOING: Count Logs confirmed by Receiver (Gate Out action)
        $outgoing = InspectionLog::whereDate('receiver_confirmed_at', $date)
            ->where('inspection_type', 'outgoing_inspection')
            ->count();

        // Stock (Count "At Site")
        $stockSite = MasterIsotank::where('status', 'active')
            ->where('location', 'SMGRS')
            ->count();
        
        $stockOther = MasterIsotank::where('status', 'active')
            ->where('location', '!=', 'SMGRS') 
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->count();

        $summary = [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'stock_site' => $stockSite,
            'stock_other' => $stockOther,
        ];

        // 2. Issues (Exception Report)
        $todaysLogs = InspectionLog::with('isotank')
            ->whereDate('created_at', $date)
            ->get();
        
        $issues = [];
        foreach ($todaysLogs as $log) {
            $faults = [];
            $checklist = [
                'surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover',
                'valve_condition', 'valve_position', 'pipe_joint'
            ];
            
            foreach ($checklist as $item) {
                if (in_array($log->$item, ['not_good', 'need_attention'])) {
                    $faults[] = ucfirst(str_replace('_', ' ', $item)) . " (" . strtoupper(str_replace('_', ' ', $log->$item)) . ")";
                }
            }

            if (!empty($faults)) {
                $issues[] = [
                    'iso_number' => $log->isotank->iso_number,
                    'type' => $log->inspection_type,
                    'notes' => implode(', ', $faults)
                ];
            }
        }

        // 3. Inspection Reports
        $inspectionLogs = InspectionLog::with(['isotank', 'inspector'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Maintenance Updates
        $completedMaintenance = MaintenanceJob::with(['isotank', 'completedBy'])
            ->whereDate('updated_at', $date)
            ->where('status', 'completed')
            ->get();
        
        $outstandingMaintenance = MaintenanceJob::with('isotank')
            ->where('status', 'open')
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->get();

        $maintenance = [
            'completed' => $completedMaintenance,
            'outstanding' => $outstandingMaintenance,
        ];
        
        return compact('dateFormatted', 'summary', 'issues', 'inspectionLogs', 'maintenance');
    }

    private function formatStatus($code) {
        $map = [
            'ready_to_fill' => 'Ready / Empty',
            'filled' => 'Filled',
            'under_maintenance' => 'Maintenance',
            'cleaning' => 'Cleaning',
            'ongoing_inspection' => 'Inspection'
        ];
        return $map[$code] ?? ucfirst(str_replace('_', ' ', $code ?: 'Unknown'));
    }
}
