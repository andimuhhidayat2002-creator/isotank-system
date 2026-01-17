<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InspectionJob;
use App\Models\MaintenanceJob;
use App\Models\MasterIsotank;
use App\Models\MasterIsotankCalibrationStatus;
use App\Models\MasterIsotankMeasurementStatus;
use App\Models\ActivityUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active_isotanks' => MasterIsotank::where('status', 'active')->count(),
            'open_inspections' => InspectionJob::where('status', 'open')->count(),
            'open_maintenance' => MaintenanceJob::where('status', 'open')->count(),
        ];

        // Summary by location and status with activity counts
        $locationSummary = MasterIsotank::select('location')
            ->selectRaw("COUNT(CASE WHEN status = 'active' THEN 1 END) as active")
            ->selectRaw("COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive")
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("(SELECT COUNT(DISTINCT isotank_id) FROM inspection_jobs WHERE inspection_jobs.isotank_id IN (SELECT id FROM master_isotanks as mi WHERE mi.location = master_isotanks.location) AND activity_type = 'incoming_inspection' AND status = 'open') as incoming")
            ->selectRaw("(SELECT COUNT(DISTINCT isotank_id) FROM inspection_jobs WHERE inspection_jobs.isotank_id IN (SELECT id FROM master_isotanks as mi WHERE mi.location = master_isotanks.location) AND activity_type = 'outgoing_inspection' AND status = 'open') as outgoing")
            ->selectRaw("(SELECT COUNT(DISTINCT isotank_id) FROM maintenance_jobs WHERE maintenance_jobs.isotank_id IN (SELECT id FROM master_isotanks as mi WHERE mi.location = master_isotanks.location) AND status = 'open') as maintenance")
            ->selectRaw("(SELECT COUNT(DISTINCT isotank_id) FROM vacuum_suction_activities WHERE vacuum_suction_activities.isotank_id IN (SELECT id FROM master_isotanks as mi WHERE mi.location = master_isotanks.location) AND completed_at IS NULL) as vacuum")
            ->selectRaw("(SELECT COUNT(DISTINCT isotank_id) FROM calibration_logs WHERE calibration_logs.isotank_id IN (SELECT id FROM master_isotanks as mi WHERE mi.location = master_isotanks.location) AND status = 'planned') as calibration")
            ->groupBy('location')
            ->get()
            ->map(function ($item) {
                return [
                    'location' => $item->location ?: 'Unknown',
                    'active' => $item->active,
                    'inactive' => $item->inactive,
                    'total' => $item->total,
                    'incoming' => $item->incoming,
                    'outgoing' => $item->outgoing,
                    'maintenance' => $item->maintenance,
                    'vacuum' => $item->vacuum,
                    'calibration' => $item->calibration,
                ];
            });
        // Vacuum Alerts (11 months)
        $vacuumAlerts = MasterIsotankMeasurementStatus::where('vacuum_mtorr', '>', 0)
            ->where('last_measurement_at', '<', now()->subMonths(10))
            ->with('isotank')
            ->get();

        // Calibration Alerts
        $calibrationAlerts = MasterIsotankCalibrationStatus::where('status', '!=', 'valid')
            ->orWhere('valid_until', '<', now()->addMonth())
            ->with('isotank')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'isotanks_by_location' => $locationSummary,
                'vacuum_alerts' => $vacuumAlerts,
                'calibration_alerts' => $calibrationAlerts,
            ],
        ]);
    }
}
