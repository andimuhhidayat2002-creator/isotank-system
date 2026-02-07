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
use App\Models\CalibrationLog;
use Illuminate\Support\Str;

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
        
        // 2. ACTIVITY STATS (Throughput)
        $incomingStats = \App\Models\InspectionJob::whereBetween('inspection_jobs.created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'incoming_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        $outgoingStartedStats = \App\Models\InspectionJob::whereBetween('inspection_jobs.created_at', [$startOfWeek, $endOfWeek])
            ->where('activity_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        $outgoingOfficialStats = \App\Models\InspectionLog::whereBetween('inspection_logs.receiver_confirmed_at', [$startOfWeek, $endOfWeek])
            ->where('inspection_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_logs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // Helper to format breakdown
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

        // Total YTD (Approximation)
        $incomingTotal = \App\Models\InspectionJob::where('activity_type', 'incoming_inspection')->count();
        $outgoingTotal = \App\Models\InspectionLog::whereNotNull('receiver_confirmed_at')->count();
        
        $stats = [
            'date_range' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
            'inspections_week' => $incomingWeek + $outgoingOfficialWeek,
            'incoming_desc' => $incomingWeek . ' ' . ($incomingWeek > 0 ? '(' . $formatBreakdown($incomingStats) . ')' : ''),
            'outgoing_desc' => $outgoingOfficialWeek . ' (Official Out) ' . ($outgoingOfficialWeek > 0 ? '(' . $formatBreakdown($outgoingOfficialStats) . ')' : ''),
            'outgoing_started_desc' => $outgoingStartedStats->sum() . ' (Process Started)',
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
        // Helper to format breakdown
        $formatBreakdown = function($stats) {
            $parts = [];
            foreach($stats as $cat => $count) {
                $label = ($cat && $cat !== '') ? $cat : 'T75';
                if (isset($parts[$label])) $parts[$label] += $count;
                else $parts[$label] = $count;
            }
            $str = [];
            foreach($parts as $l => $c) $str[] = "$l: $c";
            return implode(', ', $str);
        };

        // INCOMING: Count Jobs created today (Admin "Gate In" action)
        $incomingStats = InspectionJob::whereDate('inspection_jobs.created_at', $date)
            ->where('activity_type', 'incoming_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');
        
        // OUTGOING STARTED: Admin created outgoing job process
        $outgoingStartedStats = InspectionJob::whereDate('inspection_jobs.created_at', $date)
            ->where('activity_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_jobs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // OFFICIAL OUTGOING: Receiver confirmation today
        $outgoingOfficialStats = InspectionLog::whereDate('inspection_logs.receiver_confirmed_at', $date)
            ->where('inspection_type', 'outgoing_inspection')
            ->join('master_isotanks', 'inspection_logs.isotank_id', '=', 'master_isotanks.id')
            ->selectRaw('master_isotanks.tank_category, count(*) as count')
            ->groupBy('master_isotanks.tank_category')
            ->pluck('count', 'tank_category');

        // Stock (Count "At Site")
        $stockSiteStats = MasterIsotank::where('status', 'active')
            ->where('location', 'SMGRS')
            ->selectRaw('tank_category, count(*) as count')
            ->groupBy('tank_category')
            ->pluck('count', 'tank_category');
        
        $stockOtherStats = MasterIsotank::where('status', 'active')
            ->where('location', '!=', 'SMGRS') 
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->selectRaw('tank_category, count(*) as count')
            ->groupBy('tank_category')
            ->pluck('count', 'tank_category');

        // NEW REQUESTED STATS:
        $openMaintenanceCount = MaintenanceJob::whereIn('status', ['open', 'on_progress', 'not_complete', 'deferred'])->count();
        $inspectionsTodayCount = InspectionLog::whereDate('created_at', $date)->count();
        
        // Calibration Progress for current activities
        // Track jobs created today OR jobs completed today to show progress of "Calibration Activities"
        $todaysCalJobs = CalibrationLog::whereDate('created_at', $date)
            ->orWhereDate('updated_at', $date)
            ->whereIn('status', ['planned', 'completed', 'rejected'])
            ->get();
        
        $totalCalJobs = $todaysCalJobs->count();
        $completedCalJobs = $todaysCalJobs->where('status', 'completed')->count();
        $calProgress = $totalCalJobs > 0 ? round(($completedCalJobs / $totalCalJobs) * 100, 2) : 0;

        // FILLING STATUS BREAKDOWN
        $fillingStatusRaw = MasterIsotank::select('filling_status_code', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->where('status', 'active')
            ->groupBy('filling_status_code')
            ->orderBy('count', 'desc')
            ->get();
        
        $fillingStatusFormatted = $fillingStatusRaw->mapWithKeys(function($item) {
            $label = $item->filling_status_code ? ucfirst(str_replace('_', ' ', $item->filling_status_code)) : 'No Status';
            return [$label => $item->count];
        })->toArray();

        $summary = [
            'incoming' => $incomingStats->sum(),
            'incoming_details' => $formatBreakdown($incomingStats),
            
            'outgoing_started' => $outgoingStartedStats->sum(),
            'outgoing_started_details' => $formatBreakdown($outgoingStartedStats),
            
            'outgoing_official' => $outgoingOfficialStats->sum(),
            'outgoing_official_details' => $formatBreakdown($outgoingOfficialStats),

            'stock_site' => $stockSiteStats->sum(),
            'stock_site_details' => $formatBreakdown($stockSiteStats),
            'stock_other' => $stockOtherStats->sum(),
            'stock_other_details' => $formatBreakdown($stockOtherStats),
            
            // New items for email body
            'open_maintenance' => $openMaintenanceCount,
            'inspections_today' => $inspectionsTodayCount,
            'calibration_progress' => $calProgress,

            'inspections_today' => $inspectionsTodayCount,
            'calibration_progress' => $calProgress,

            'filling_status_breakdown' => $fillingStatusFormatted,
        ];

        // PYTHON INTEGRATION: Enhancing Analytics
        try {
            $scriptPath = base_path('scripts/report_analytics.py');
            $dateStr = $date->format('Y-m-d');
            
            // Use Process to execute python (same logic as Dashboard)
            $process = new \Symfony\Component\Process\Process(['python3', $scriptPath, 'daily', $dateStr]);
            $process->setTimeout(10); 
            $process->run();

            if ($process->isSuccessful()) {
                 $pyData = json_decode($process->getOutput(), true);
                 if (json_last_error() === JSON_ERROR_NONE && !isset($pyData['error'])) {
                      // Merge Python charts/insights into summary
                      $summary['stock_chart'] = $pyData['ascii_chart_stock'] ?? '';
                      $summary['trend_analysis'] = $pyData['filling_distribution'] ?? [];
                      // Future: Overlay creating precise stats from Python if needed
                 } else {
                      \Illuminate\Support\Facades\Log::warning("Report Python JSON Error: " . ($pyData['error'] ?? 'Invalid JSON'));
                 }
            } else {
                 \Illuminate\Support\Facades\Log::warning("Report Python Failed: " . $process->getErrorOutput());
            }
        } catch (\Exception $e) {
             \Illuminate\Support\Facades\Log::error("Report Python Exception: " . $e->getMessage());
        }

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
            ->where('status', 'closed') // Fixed status check
            ->get();
        
        $outstandingMaintenance = MaintenanceJob::with('isotank')
            ->whereIn('status', ['open', 'on_progress', 'not_complete', 'deferred'])
            ->get();

        $maintenance = [
            'completed' => $completedMaintenance,
            'outstanding' => $outstandingMaintenance,
        ];

        // 5. Calibration Activities (The "Manual Activities" created by admin)
        $calibrationItems = CalibrationLog::with('isotank')
            ->whereDate('created_at', $date)
            ->orWhere(function($q) use ($date) {
                $q->whereDate('updated_at', $date)
                  ->where('status', 'completed');
            })
            ->orderBy('id', 'desc')
            ->get();
        
        return compact('dateFormatted', 'summary', 'issues', 'inspectionLogs', 'maintenance', 'calibrationItems');
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
