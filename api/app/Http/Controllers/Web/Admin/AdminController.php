<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExcelUploadLog;
use App\Models\InspectionJob;
use App\Models\InspectionLog;
use App\Models\MaintenanceJob;
use App\Models\MasterIsotank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\IsotankUpload;
use App\Models\ActivityUpload;
use App\Models\CalibrationLog;
use App\Models\VacuumLog;
use App\Models\MasterIsotankCalibrationStatus;
use App\Models\MasterIsotankMeasurementStatus;
use App\Models\VacuumSuctionActivity;
use App\Models\MasterLatestInspection;
use Illuminate\Support\Facades\Mail;
use App\Mail\DailyOperationsReport;
use App\Models\ClassSurvey;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        // 1) Global summary (all locations combined)
        $globalStats = [
             'total_active' => MasterIsotank::where('status', 'active')->count(),
             'open_maintenance' => MaintenanceJob::where('status', '!=', 'closed')->count(),
             'open_inspections' => InspectionJob::whereIn('status', ['open', 'in_progress'])->count(),
             'calibration_alerts' => MasterIsotankCalibrationStatus::where('status', '!=', 'valid')
                  ->orWhere('valid_until', '<', now()->addMonth())
                  ->count()
        ];

        // 2) Location distribution (Breakdown)
        $locations = MasterIsotank::select('location')
            ->selectRaw('count(*) as active_count')
            ->selectRaw('count(distinct owner) as owner_count')
            ->selectRaw('count(distinct manufacturer) as manufacturer_count')
            // Fix: Use filling_status_code directly. Filled only for 'filled'. Everything else (that is not null) is 'empty'.
            ->selectRaw("sum(case when filling_status_code != 'filled' and filling_status_code is not null and filling_status_code != '' then 1 else 0 end) as empty_count")
            ->selectRaw("sum(case when filling_status_code = 'filled' then 1 else 0 end) as filled_count")
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->where('status', 'active')
            ->groupBy('location')
            ->orderBy('location')
            ->get();

        // Location Breakdowns (Owner & Manufacturer)
        // Group by Location for easy access in View: $ownerBreakdown['SMGRS'] -> Collection of rows
        $ownerBreakdown = MasterIsotank::where('status', 'active')
            ->whereNotNull('location')->where('location', '!=', '')
            ->select('location', 'owner', DB::raw('count(*) as count'))
            ->groupBy('location', 'owner')
            ->get()
            ->groupBy('location');

        $manufacturerBreakdown = MasterIsotank::where('status', 'active')
            ->whereNotNull('location')->where('location', '!=', '')
            ->select('location', 'manufacturer', DB::raw('count(*) as count'))
            ->groupBy('location', 'manufacturer')
            ->get()
            ->groupBy('location');

        // 3) Alerts across all isotanks
        // Limit query to prevent memory overflow
        $vacuumAlerts = MasterIsotankMeasurementStatus::where('vacuum_mtorr', '>', 8)
             ->orWhere('last_measurement_at', '<', now()->subMonths(11))
             ->with('isotank:id,iso_number,location')
             ->limit(5)
             ->get();

        // Optimized Calibration Alerts Query (Limit 5)
        $calibrationAlerts = \App\Models\MasterIsotankComponent::select('id', 'isotank_id', 'expiry_date', 'component_type', 'position_code', 'serial_number')
             ->where('expiry_date', '<', now()->addMonths(1))
             ->with('isotank:id,iso_number,location')
             ->orderBy('expiry_date', 'asc')
             ->limit(5)
             ->get();

        // Filling Status Statistics
        $fillingStatusStats = [];
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $count = MasterIsotank::where('status', 'active')
                ->where('filling_status_code', $code)
                ->count();
            if ($count > 0) {
                $fillingStatusStats[] = [
                    'code' => $code,
                    'description' => $description,
                    'count' => $count
                ];
            }
        }
        
    // No status count
    $noStatusCount = MasterIsotank::where('status', 'active')
        ->whereNull('filling_status_code')
        ->count();
    if ($noStatusCount > 0) {
        $fillingStatusStats[] = [
            'code' => 'no_status',
            'description' => 'No Status',
            'count' => $noStatusCount
        ];
    }
    
    // Saved Recipient Emails
    $savedEmails = \Illuminate\Support\Facades\Cache::get('daily_report_recipients', 'manager@ptkayan.com');

    return view('admin.dashboard', compact(
        'globalStats', 
        'locations', 
        'ownerBreakdown', 
        'manufacturerBreakdown',
        'vacuumAlerts', 
        'calibrationAlerts',
        'fillingStatusStats',
        'savedEmails'
    ));
    }

    public function locationDetail($location)
    {
        // Decode location (URL encoding)
        $location = urldecode($location);

        // A. Asset Summary
        $isotanks = MasterIsotank::where('location', $location)->where('status', 'active')->pluck('id');
        
        $assetSummary = [
            'total_isotanks' => $isotanks->count(),
            'open_maintenance' => MaintenanceJob::whereIn('isotank_id', $isotanks)->where('status', '!=', 'closed')->count(),
            'expired_calibration' => MasterIsotankCalibrationStatus::whereIn('isotank_id', $isotanks)
                ->where(function ($q) {
                    $q->where('status', 'expired')->orWhere('valid_until', '<', now());
                })->count(),
            'high_vacuum' => MasterIsotankMeasurementStatus::whereIn('isotank_id', $isotanks)
                ->where('vacuum_mtorr', '>', 8) // > 8 mTorr
                ->count(),
        ];

        // B. Maintenance Snapshot (Open/Progress/Closed)
        $maintenanceStats = MaintenanceJob::whereIn('isotank_id', $isotanks)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Frequent failed items at this location
        $frequentFailures = MaintenanceJob::whereIn('isotank_id', $isotanks)
            ->select('source_item', DB::raw('count(*) as count'))
            ->groupBy('source_item')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // C. Vacuum Snapshot (Exceed list > 8 mTorr)
        $vacuumExceed = MasterIsotankMeasurementStatus::whereIn('isotank_id', $isotanks)
            ->where('vacuum_mtorr', '>', 8)
            ->with('isotank')
            ->get();

        // D. Calibration Snapshot (Expiring)
        $expiringCalibration = MasterIsotankCalibrationStatus::whereIn('isotank_id', $isotanks)
            ->where('valid_until', '<', now()->addMonths(3)) // 90 days as general alert
            ->where('valid_until', '>', now())
            ->with('isotank')
            ->orderBy('valid_until')
            ->get();

        // E. Filling Status Snapshot (Updated to use new codes)
        $fillingStats = [];
        
        // Get all isotanks at this location
        $allIsotanks = MasterIsotank::whereIn('id', $isotanks)->get();
        
        // Count by each status code
        foreach (MasterIsotank::getValidFillingStatuses() as $code => $description) {
            $count = $allIsotanks->where('filling_status_code', $code)->count();
            if ($count > 0) {
                $fillingStats[$code] = [
                    'description' => $description,
                    'count' => $count
                ];
            }
        }
        
        // Count unspecified
        $unspecifiedCount = $allIsotanks->filter(function($tank) {
            return empty($tank->filling_status_code);
        })->count();
        
        if ($unspecifiedCount > 0) {
            $fillingStats['unspecified'] = [
                'description' => 'Not Specified',
                'count' => $unspecifiedCount
            ];
        }
        
        // Legacy support: Calculate filled vs empty for backward compatibility
        // 'filled' is specifically filled status. 'empty' captures ready_to_fill and other non-cargo stages.
        $legacyFillingStats = [
            'empty' => $allIsotanks->whereIn('filling_status_code', ['ready_to_fill', 'ongoing_inspection', 'under_maintenance', 'waiting_team_calibration', 'class_survey'])->count(),
            'filled' => $allIsotanks->where('filling_status_code', 'filled')->count(),
            'unspecified' => $unspecifiedCount,
        ];

        // F. Detailed Lists
        $isotankList = MasterIsotank::whereIn('id', $isotanks)->orderBy('iso_number')->get();
        $recentActivities = InspectionJob::whereIn('isotank_id', $isotanks)->with('isotank')->latest()->limit(10)->get();

        return view('admin.dashboard.location_detail', compact(
            'location', 
            'assetSummary', 
            'maintenanceStats', 
            'frequentFailures', 
            'vacuumExceed', 
            'expiringCalibration',
            'fillingStats',
            'isotankList',
            'recentActivities'
        ));
    }

    // Global Statistics Modules
    
    public function maintenanceStatistics() { 
        // 3) Maintenance Statistics (GLOBAL)
        // Most frequent failed items (all locations)
        $frequentFailures = MaintenanceJob::select('source_item', DB::raw('count(*) as count'))
            ->groupBy('source_item')->orderByDesc('count')->limit(10)->get();

        // Maintenance status distribution
        $statusDistrib = MaintenanceJob::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status')->toArray();

        // Maintenance count per isotank (Top 20)
        $maintenancePerIsotank = MaintenanceJob::select('isotank_id', DB::raw('count(*) as count'))
            ->with('isotank:id,iso_number,location')
            ->groupBy('isotank_id')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Maintenance by location
        $maintenanceByLocation = MaintenanceJob::join('master_isotanks', 'maintenance_jobs.isotank_id', '=', 'master_isotanks.id')
            ->select('master_isotanks.location', DB::raw('count(*) as count'))
            ->groupBy('master_isotanks.location')
            ->orderByDesc('count')
            ->get();
            
        return view('admin.dashboard.maintenance_stats', compact(
            'frequentFailures', 
            'statusDistrib', 
            'maintenancePerIsotank', 
            'maintenanceByLocation'
        ));
    }

    public function vacuumMonitoring() {
        // 4) Vacuum Monitoring (GLOBAL)
        
        // Vacuum exceed frequency (>8 mTorr) - Historical Logs
        $exceedFrequency = VacuumLog::where('vacuum_value_mtorr', '>', 8)
            ->select('isotank_id', DB::raw('count(*) as count'))
            ->with('isotank:id,iso_number,location')
            ->groupBy('isotank_id')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Vacuum history (Suction Activities)
        $suctionHistory = VacuumSuctionActivity::with('isotank:id,iso_number')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Vacuum expiry alert (11 months)
        $expiryAlerts = MasterIsotankMeasurementStatus::where('last_measurement_at', '<', now()->subMonths(11))
            ->with('isotank:id,iso_number,location')
            ->get();
            
        // Current Exceed List (>8 mTorr)
        $currentExceed = MasterIsotankMeasurementStatus::where('vacuum_mtorr', '>', 8)
            ->with('isotank:id,iso_number,location')
            ->get();

        // 1. Trend Analysis (Last 12 Months)
        $trendData = VacuumLog::select(
                DB::raw("DATE_FORMAT(check_datetime, '%Y-%m') as month"), 
                DB::raw('AVG(vacuum_value_mtorr) as avg_vacuum')
            )
            ->where('check_datetime', '>=', now()->subYear())
            ->groupBy(DB::raw("DATE_FORMAT(check_datetime, '%Y-%m')")) // Fix: Group by expression, not alias
            ->orderBy(DB::raw("DATE_FORMAT(check_datetime, '%Y-%m')"))
            ->get();

        // 2. Comparison (Current vs Last Year)
        // Get active tanks
        $activeTanks = MasterIsotank::with('measurementStatus')->where('status', 'active')->limit(50)->get();
        $comparisonData = [];

        foreach ($activeTanks as $tank) {
            // Latest reading
            $latest = VacuumLog::where('isotank_id', $tank->id)->orderByDesc('check_datetime')->first();
            
            // Self-Healing: If master status is stale (differs from latest log), update it.
            try {
                if ($latest && $tank->measurementStatus) {
                    $masterVal = (float)$tank->measurementStatus->vacuum_mtorr;
                    $logVal = (float)$latest->vacuum_value_mtorr;
                    
                    // If diff > 0.01 or master is null/old
                    if (abs($masterVal - $logVal) > 0.01) {
                        $tank->measurementStatus->update([
                            'vacuum_mtorr' => $logVal,
                            'temperature' => $latest->temperature,
                            'last_measurement_at' => $latest->check_datetime
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Ignore self-healing errors to prevent page crash
                \Illuminate\Support\Facades\Log::error('Vacuum Self-Healing Error: ' . $e->getMessage());
            }
            
            if ($latest) {
                // Historical reading (~1 year ago +/- 1 month)
                $oneYearAgo = now()->subYear();
                $historical = VacuumLog::where('isotank_id', $tank->id)
                    ->whereBetween('check_datetime', [$oneYearAgo->copy()->subMonth(), $oneYearAgo->copy()->addMonth()])
                    ->orderByDesc('check_datetime')
                    ->first();

                // If no exact 1 year, try any oldest log > 6 months
                if (!$historical) {
                    $historical = VacuumLog::where('isotank_id', $tank->id)
                        ->where('check_datetime', '<', now()->subMonths(6))
                        ->orderBy('check_datetime', 'asc')
                        ->first();
                }

                $comparisonData[] = [
                    'iso_number' => $tank->iso_number,
                    'current_val' => $latest->vacuum_value_mtorr,
                    'current_date' => $latest->check_datetime,
                    'history_val' => $historical ? $historical->vacuum_value_mtorr : null,
                    'history_date' => $historical ? $historical->check_datetime : null,
                    'change' => ($historical && $latest) ? ($latest->vacuum_value_mtorr - $historical->vacuum_value_mtorr) : null
                ];
            }
        }

        return view('admin.dashboard.vacuum_monitoring', compact(
            'exceedFrequency',
            'suctionHistory',
            'expiryAlerts',
            'currentExceed',
            'trendData',
            'comparisonData'
        ));
    }

    public function calibrationMonitoring() {
        try {
            // 5) Calibration Monitoring (GLOBAL)
            
            // Calibration status summary
            $statusSummary = MasterIsotankCalibrationStatus::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Expiring calibration (30 / 60 / 90 days)
            $expiring30 = MasterIsotankCalibrationStatus::whereDate('valid_until', '<=', now()->addDays(30))
                ->whereDate('valid_until', '>=', now())
                ->count();
            
            $expiring60 = MasterIsotankCalibrationStatus::whereDate('valid_until', '<=', now()->addDays(60))
                ->whereDate('valid_until', '>=', now())
                ->count();

            $expiring90 = MasterIsotankCalibrationStatus::whereDate('valid_until', '<=', now()->addDays(90))
                ->whereDate('valid_until', '>=', now())
                ->count();

            // Rejected calibration history (Top 20 recent)
            $rejectedHistory = CalibrationLog::where('status', 'rejected')
                ->with('isotank:id,iso_number,location', 'performer:id,name')
                ->latest()
                ->limit(20)
                ->get();

            // Calibration by vendor
            $byVendor = CalibrationLog::select('vendor', DB::raw('count(*) as count'))
                ->whereNotNull('vendor')
                ->groupBy('vendor')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Detailed Expiring List (Alerts) - Expired and Next 3 Months
            // OPTIMIZED: Select specific columns, Limit 200, Eager Load minimal fields
            $expiringAlertsDetailed = \App\Models\MasterIsotankComponent::select(
                    'id', 'isotank_id', 'component_type', 'serial_number', 'position_code', 'expiry_date'
                )
                ->where('expiry_date', '<', now()->addMonths(3))
                ->with('isotank:id,iso_number,location')
                ->orderBy('expiry_date', 'asc')
                ->limit(200)
                ->get();

            // FORCE RENDER TO CATCH BLADE ERRORS
            $view = view('admin.dashboard.calibration_monitoring', compact(
                'statusSummary',
                'expiring30',
                'expiring60',
                'expiring90',
                'rejectedHistory',
                'byVendor',
                'expiringAlertsDetailed'
            ));
            return $view->render();

        } catch (\Exception $e) {
            return response("<h1>RENDER ERROR DETECTED</h1><pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>");
        }
    }

    public function exportCalibrationAlerts()
    {
        try {
            $fileName = 'calibration_attention_list_' . date('Y-m-d') . '.xlsx';
            // Try using the root alias which usually links to the Facade
            return \Excel::download(new \App\Exports\CalibrationAlertsExport, $fileName);
        } catch (\Throwable $e) {
             // Fallback to CSV if Excel fails
             \Illuminate\Support\Facades\Log::error("Excel export failed: " . $e->getMessage());
             return $this->exportCalibrationAlertsCsvFallback();
        }
    }

    private function exportCalibrationAlertsCsvFallback() {
         $fileName = 'calibration_attention_list_' . date('Y-m-d') . '.csv';
         $alerts = \App\Models\MasterIsotankComponent::where('expiry_date', '<', now()->addMonths(3))
            ->with('isotank:id,iso_number,location')
            ->orderBy('expiry_date', 'asc')->get();

        $headers = [
            'Content-type' => 'text/csv', 
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        $columns = ['Expiry Date', 'Days Left', 'Isotank Number', 'Location', 'Component Type', 'Position', 'Serial Number'];

        $callback = function() use ($alerts, $columns) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM 
            fputcsv($file, $columns, ';');
            foreach ($alerts as $item) {
                $daysLeft = $item->expiry_date ? now()->diffInDays($item->expiry_date, false) : 0;
                fputcsv($file, [
                    $item->expiry_date ? $item->expiry_date->format('Y-m-d') : 'N/A',
                    (int)$daysLeft,
                    $item->isotank->iso_number ?? '-',
                    $item->isotank->location ?? '-',
                    $item->component_type,
                    $item->position_code ?? '-',
                    $item->serial_number ?? '-'
                ], ';');
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }


    
    public function inspectionPerformance() {
        return view('admin.dashboard.inspection_performance');
    }
    
    public function outgoingQuality() {
        return view('admin.dashboard.outgoing_quality');
    }

    public function isotanks(Request $request)
    {
        $query = MasterIsotank::with(['measurementStatus', 'calibrationStatuses', 'classSurveys']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('iso_number', 'LIKE', "%{$search}%");
        }

        $isotanks = $query->orderBy('iso_number')->get();
        return view('admin.isotanks', compact('isotanks'));
    }

    public function showIsotank($id)
    {
        $isotank = MasterIsotank::with([
            'measurementStatus', 
            'calibrationStatuses', 
            'classSurveys',
            'components', 
            'latestInspection.inspector'
        ])->findOrFail($id);

        $inspections = \App\Models\InspectionLog::with('inspector')
            ->where('isotank_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $maintenance = \App\Models\MaintenanceJob::with('completedBy')
            ->where('isotank_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $vacuumLogs = \App\Models\VacuumLog::where('isotank_id', $id)
            ->orderBy('check_datetime', 'desc')
            ->get();
            
        return view('admin.isotanks.show', compact('isotank', 'inspections', 'maintenance', 'vacuumLogs'));
    }

    public function storeIsotank(Request $request)
    {
        $validated = $request->validate([
            'iso_number' => 'required|unique:master_isotanks',
            'product' => 'nullable|string',
            'owner' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'model_type' => 'nullable|string',
            'manufacturer_serial_number' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'initial_pressure_test_date' => 'nullable|date',
            'csc_initial_test_date' => 'nullable|date',
            'class_survey_expiry_date' => 'nullable|date',
            'csc_survey_expiry_date' => 'nullable|date',
        ]);
        
        MasterIsotank::create($validated);
        return back()->with('success', 'Isotank created');
    }

    public function toggleIsotankStatus($id)
    {
        $isotank = MasterIsotank::findOrFail($id);
        $newStatus = $isotank->status === 'active' ? 'inactive' : 'active';
        $isotank->update(['status' => $newStatus]);
        return back()->with('success', "Isotank set to $newStatus");
    }

    public function storeClassSurvey(Request $request, $id)
    {
        $request->validate([
            'survey_date' => 'required|date',
            'next_survey_date' => 'required|date|after:survey_date',
        ]);

        ClassSurvey::create([
            'isotank_id' => $id,
            'survey_date' => $request->survey_date,
            'next_survey_date' => $request->next_survey_date,
        ]);

        return back()->with('success', 'Class Survey updated successfully.');
    }

    public function bulkUpdateClassSurvey(Request $request) {
        $request->validate([
            'isotank_ids' => 'required|array',
            'isotank_ids.*' => 'exists:master_isotanks,id',
            'survey_date' => 'required|date',
            'next_survey_date' => 'required|date|after:survey_date',
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->isotank_ids as $id) {
                ClassSurvey::create([
                    'isotank_id' => $id,
                    'survey_date' => $request->survey_date,
                    'next_survey_date' => $request->next_survey_date,
                ]);
            }
            DB::commit();
            return back()->with('success', 'Bulk Class Survey updated for ' . count($request->isotank_ids) . ' isotanks.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk update failed: ' . $e->getMessage());
        }
    }

    public function activities(Request $request)
    {
        // 1. Fetch Logs (Optimized: Select specific columns to avoid 'Out of sort memory' error)
        $logs = ActivityUpload::select('id', 'activity_type', 'filename', 'created_at', 'success_count', 'error_count', 'total_rows', 'uploaded_by')
            ->with('uploader')
            ->latest()
            ->paginate(10);
        
        $search = $request->search;

        // 2. Fetch Pending Jobs with Safety Checks (has 'isotank')
        $insQuery = InspectionJob::with('isotank')->where('status', 'open')->has('isotank');
        $maintQuery = MaintenanceJob::with('isotank')->where('status', 'open')->has('isotank');
        $calQuery = CalibrationLog::with('isotank')->where('status', 'planned')->has('isotank');

        if ($search) {
            $checkIso = function($q) use ($search) {
                $q->where('iso_number', 'LIKE', "%{$search}%");
            };
            $insQuery->whereHas('isotank', $checkIso);
            $maintQuery->whereHas('isotank', $checkIso);
            $calQuery->whereHas('isotank', $checkIso);
        }

        $pendingInspections = $insQuery->latest()->get();
        $pendingMaintenance = $maintQuery->latest()->get();
        $pendingCalibrations = $calQuery->latest()->get();

        return view('admin.activities', compact('logs', 'pendingInspections', 'pendingMaintenance', 'pendingCalibrations'));
    }

    public function storeManualActivity(Request $request)
    {
        $validated = $request->validate([
            'activity_type' => 'required|in:incoming_inspection,outgoing_inspection,maintenance,calibration',
            'iso_number' => 'required|exists:master_isotanks,iso_number',
            'planned_date' => 'nullable|date',
            'destination' => 'required_if:activity_type,outgoing_inspection',
            'receiver_name' => 'required_if:activity_type,outgoing_inspection',
            'item_name' => 'required_if:activity_type,maintenance',
            'item_names' => 'required_if:activity_type,calibration|array',
            'item_names.*' => 'string',
            'description' => 'required_if:activity_type,maintenance,calibration',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'vendor' => 'nullable|string',
            'filling_status_code' => 'required_if:activity_type,incoming_inspection,outgoing_inspection',
            'filling_status_desc' => 'nullable|string',
            'serial_numbers' => 'required_if:activity_type,calibration|array',
        ]);

        $isotank = MasterIsotank::where('iso_number', $request->iso_number)->first();

        if ($isotank->status !== 'active') {
            return back()->with('error', "Isotank {$request->iso_number} is inactive.");
        }

        try {
            DB::beginTransaction();

            switch ($request->activity_type) {
                case 'incoming_inspection':
                case 'outgoing_inspection':
                    InspectionJob::create([
                        'isotank_id' => $isotank->id,
                        'activity_type' => $request->activity_type,
                        'planned_date' => $request->planned_date ?? now(),
                        'destination' => $request->destination,
                        'receiver_name' => $request->activity_type === 'outgoing_inspection' ? $request->receiver_name : null,
                        'filling_status_code' => $request->filling_status_code,
                        'filling_status_desc' => $request->filling_status_desc,
                        'status' => 'open'
                    ]);
                    
                    if ($request->activity_type === 'incoming_inspection') {
                        // INCOMING FLOW (LOCKED): Force location SMGRS + Update Filling Status
                        $isotank->update([
                            'location' => 'SMGRS',
                            'filling_status_code' => $request->filling_status_code,
                            'filling_status_desc' => $request->filling_status_desc
                        ]);
                    } elseif ($request->activity_type === 'outgoing_inspection') {
                        // OUTGOING FLOW (User Request): Update Filling Status immediately
                        $isotank->update([
                            'filling_status_code' => $request->filling_status_code,
                            'filling_status_desc' => $request->filling_status_desc
                        ]);
                    }
                    break;

                case 'maintenance':
                    MaintenanceJob::create([
                        'isotank_id' => $isotank->id,
                        'source_item' => $request->item_name,
                        'description' => $request->description,
                        'priority' => $request->priority ?? 'normal',
                        'planned_date' => $request->planned_date,
                        'status' => 'open'
                    ]);
                    break;

                case 'calibration':
                    if (is_array($request->item_names) && count($request->item_names) > 0) {
                        foreach ($request->item_names as $itemName) {
                            $serialKey = $itemName; // The key used in serial_numbers array on frontend
                            // Handling potential key transformations if any (frontend sends raw string)
                            // $request->serial_numbers is an array: ['Pressure Gauge' => 'SN123', 'PSV 1' => 'SN456']
                            $serial = $request->serial_numbers[$serialKey] ?? null;

                            CalibrationLog::create([
                                'isotank_id' => $isotank->id,
                                'item_name' => $itemName,
                                'serial_number' => $serial,
                                'description' => $request->description ?? '-',
                                'planned_date' => $request->planned_date,
                                'vendor' => $request->vendor,
                                'status' => 'planned'
                            ]);
                        }
                    }
                    break;
            }

            DB::commit();
            return back()->with('success', 'Manual activity planned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating activity: ' . $e->getMessage());
        }
    }

    public function deleteInspectionJob($id) {
        $job = InspectionJob::findOrFail($id);
        if ($job->status !== 'open') return back()->with('error', 'Only open jobs can be deleted.');
        $job->delete();
        return back()->with('success', 'Inspection job removed.');
    }

    public function deleteMaintenanceJob($id) {
        $job = MaintenanceJob::findOrFail($id);
        if ($job->status !== 'open') return back()->with('error', 'Only open jobs can be deleted.');
        $job->delete();
        return back()->with('success', 'Maintenance job removed.');
    }

    public function deleteCalibrationJob($id) {
        $job = CalibrationLog::findOrFail($id);
        if ($job->status !== 'planned') return back()->with('error', 'Only planned jobs can be deleted.');
        $job->delete();
        return back()->with('success', 'Calibration job removed.');
    }
    
    public function inspectionLogs() {
        $logs = InspectionLog::with(['isotank', 'inspector'])->latest()->get();
        return view('admin.reports.inspection', compact('logs'));
    }

    public function showInspectionLog($id) {
        $log = InspectionLog::with(['isotank', 'inspector'])->findOrFail($id);
        return view('admin.reports.inspection_show', compact('log'));
    }
    
    public function maintenanceJobs() {
        $jobs = MaintenanceJob::with(['isotank', 'assignee'])->latest()->get();
        return view('admin.reports.maintenance', compact('jobs'));
    }

    public function showMaintenanceJob($id) {
        $job = MaintenanceJob::with(['isotank', 'assignee', 'creator', 'completedBy', 'triggeredByInspection'])->findOrFail($id);
        return view('admin.reports.maintenance_show', compact('job'));
    }
    
    public function vacuumActivities() {
        // Fetch all activities ordered by isotank and creation date
        $allActivities = VacuumSuctionActivity::with(['isotank', 'recorder'])
            ->orderBy('isotank_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $sessions = [];
        $tempSessions = [];

        foreach ($allActivities as $activity) {
            $isoId = $activity->isotank_id;
            
            // Logic: A new session starts IF it's Day 1 OR we don't have an active session for this ISO.
            // We removed the 'is_completed' break check because if a user erroneously marks Day 1 as completed, 
            // Day 2 should still group into the same session row instead of creating a new orphan row.
            $shouldStartNew = $activity->day_number == 1 || !isset($tempSessions[$isoId]);

            if ($shouldStartNew) {
                if (isset($tempSessions[$isoId])) {
                    $sessions[] = $tempSessions[$isoId];
                }
                
                $tempSessions[$isoId] = [
                    'isotank' => $activity->isotank,
                    'days' => [ (int)$activity->day_number => $activity ],
                    'is_completed' => $activity->completed_at ? true : false,
                    'latest_date' => $activity->created_at,
                    'start_date' => $activity->created_at,
                    // Store Day 1 data specifically for summary
                    'day1_summary' => [
                        'portable_vacuum' => $activity->portable_vacuum_value,
                        'temp' => $activity->temperature,
                        'mch_stop' => $activity->machine_vacuum_at_stop,
                    ]
                ];
            } else {
                // Add to existing session
                $tempSessions[$isoId]['days'][(int)$activity->day_number] = $activity;
                if ($activity->completed_at) {
                    $tempSessions[$isoId]['is_completed'] = true;
                }
                $tempSessions[$isoId]['latest_date'] = $activity->created_at;
                
                // Update day1_summary from later records if they carry carried-over data
                if (!$tempSessions[$isoId]['day1_summary']['portable_vacuum'] && $activity->portable_vacuum_value) {
                    $tempSessions[$isoId]['day1_summary']['portable_vacuum'] = $activity->portable_vacuum_value;
                }
                if (!$tempSessions[$isoId]['day1_summary']['mch_stop'] && $activity->machine_vacuum_at_stop) {
                    $tempSessions[$isoId]['day1_summary']['mch_stop'] = $activity->machine_vacuum_at_stop;
                }
            }
        }
        
        // Push remaining sessions
        foreach($tempSessions as $sess) {
            $sessions[] = $sess;
        }

        // Sort by latest activity date descending
        // Sort by latest activity date descending
        usort($sessions, function($a, $b) {
            return $b['latest_date'] <=> $a['latest_date'];
        });

        $sessions = collect($sessions);

        $vacuumLogs = VacuumLog::with('isotank')
            ->orderBy('check_datetime', 'desc')
            ->get();

        return view('admin.reports.vacuum', compact('sessions', 'vacuumLogs'));
    }

    public function latestInspections() {
        $logs = MasterLatestInspection::with(['isotank.components', 'inspector'])->get();
        return view('admin.reports.latest_inspections', compact('logs'));
    }

    public function calibrationLogs() {
        $logs = CalibrationLog::with(['isotank', 'creator'])->latest()->get();
        return view('admin.reports.calibration', compact('logs'));
    }

    private function getDailyReportData($date) {
        $dateFormatted = $date->format('l, d F Y');

        // 1. Movement Summary
        $incoming = InspectionLog::whereDate('created_at', $date)
            ->where('inspection_type', 'incoming_inspection')
            ->count();
        
        $outgoing = InspectionLog::whereDate('created_at', $date)
            ->where('inspection_type', 'outgoing_inspection')
            ->count();

        // Stock (Assuming SMGRS is site)
        $stockSite = MasterIsotank::where('status', 'active')
            ->where('location', 'SMGRS')
            ->count();
        
        $stockOther = MasterIsotank::where('status', 'active')
            ->where('location', '!=', 'SMGRS') // Assuming any non-empty location that isn't SMGRS is 'stock other'
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
        // Logic: Check logs created "on that date" that have any "not_good" or "need_attention" status
        $todaysLogs = InspectionLog::with('isotank')
            ->whereDate('created_at', $date)
            ->get();
        
        $issues = [];
        foreach ($todaysLogs as $log) {
            // Check general conditions
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

        // 3. Inspection Reports PDF
        $inspectionLogs = InspectionLog::with(['isotank', 'inspector'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Maintenance Updates
        $completedMaintenance = MaintenanceJob::with(['isotank', 'completedBy'])
            ->whereDate('updated_at', $date)
            ->where('status', 'completed')
            ->get();
        
        // Outstanding is "current state", not really historical. 
        // But for a report of DAY X, we probably still want "What was outstanding on DAY X?"
        // However, calculating historical state is hard. We will stick to "Currently Outstanding" or "Created before X and still open".
        // For simplicity, let's keep "Currently Outstanding" regardless of report date, or maybe "Created before Report Date and Still Open".
        // Let's stick to current outstanding logic for now as 'Snapshot'.
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

    public function previewDailyReport(Request $request) {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $data = $this->getDailyReportData($date);
        
        // Pass a flag to view to maybe hide footer or show "Preview Mode" banner
        return view('emails.daily_report', array_merge($data, ['date' => $data['dateFormatted']]));
    }

    public function sendDailyReport(Request $request, \App\Services\DailyReportExcelService $excelService) {
        $request->validate([
            'date' => 'nullable|date',
            'email' => 'required|string' // Changed to string to accept comma separated emails
        ]);

        try {
            $date = $request->date ? Carbon::parse($request->date) : Carbon::today();
            
            // Handle multiple emails (comma separated, trimmed)
            $targetEmails = array_map('trim', explode(',', $request->email));
            // Basic validation for each email
            $validEmails = array_filter($targetEmails, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            if (empty($validEmails)) {
                return back()->with('error', 'No valid email addresses provided.');
            }
            
            $data = $this->getDailyReportData($date);
            
            // Generate Excel
            $excelContent = $excelService->generate($data['dateFormatted'], $data);

            // Send Email to all valid recipients
            Mail::to($validEmails)->send(new DailyOperationsReport(
                $data['dateFormatted'], 
                $data['summary'], 
                $data['issues'], 
                $data['inspectionLogs'], 
                $data['maintenance'],
                $excelContent
            ));
            
            $emailListStr = implode(', ', $validEmails);
            
            // Save valid emails to cache for future convenience (persist for 30 days)
            \Illuminate\Support\Facades\Cache::put('daily_report_recipients', $emailListStr, now()->addDays(30));

            return back()->with('success', "Daily Report for {$data['dateFormatted']} has been sent to: {$emailListStr} (Excel Attached).");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send report: ' . $e->getMessage());
        }
    }
    public function downloadInspectionPdf($id)
    {
        $inspection = InspectionLog::with(['isotank', 'inspector', 'inspectionJob'])->findOrFail($id);
        $isotank = $inspection->isotank;
        $inspector = $inspection->inspector;
        $type = ($inspection->inspection_type == 'incoming_inspection') ? 'incoming' : 'outgoing';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inspection_report', compact('inspection', 'isotank', 'inspector', 'type'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('Inspection_' . $isotank->iso_number . '_' . $inspection->created_at->format('Ymd') . '.pdf');
    }
}
