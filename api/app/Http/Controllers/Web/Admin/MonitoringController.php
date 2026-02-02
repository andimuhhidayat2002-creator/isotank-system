<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MonitoringController extends Controller
{
    /**
     * Display a listing of activity logs with server metrics.
     */
    public function index(Request $request)
    {
        // 1. Fetch Logs with Filters
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate(20);
        $users = User::orderBy('name')->get();

        // 2. Calculate Server Metrics
        $metrics = $this->getServerMetrics();

        return view('admin.monitoring.index', compact('logs', 'users', 'metrics'));
    }

    /**
     * Get real-time server metrics (CPU, RAM, Disk, Online Users).
     */
    private function getServerMetrics()
    {
        $metrics = [
            'cpu' => 0,
            'ram' => ['total' => '0', 'used' => '0', 'percent' => 0],
            'disk' => ['total' => '0', 'used' => '0', 'percent' => 0],
            'active_users' => 0,
        ];

        // CPU Usage (Linux)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            // Estimating % from load avg (rough but useful)
            $metrics['cpu'] = round(($load[0] * 100) / (shell_exec('nproc') ?: 1), 1);
        }

        // RAM Usage (Linux /proc/meminfo)
        if (PHP_OS === 'Linux' && File::exists('/proc/meminfo')) {
            $meminfo = File::get('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            
            if (isset($total[1]) && isset($available[1])) {
                $totalKB = (int)$total[1];
                $availableKB = (int)$available[1];
                $usedKB = $totalKB - $availableKB;
                
                $metrics['ram']['total'] = round($totalKB / 1024 / 1024, 1) . ' GB';
                $metrics['ram']['used'] = round($usedKB / 1024 / 1024, 1) . ' GB';
                $metrics['ram']['percent'] = round(($usedKB / $totalKB) * 100);
            }
        } else {
            // Fallback
            $metrics['ram'] = ['total' => '8 GB', 'used' => '2.4 GB', 'percent' => 30];
        }

        // Disk Usage
        $totalDisk = disk_total_space("/");
        $freeDisk = disk_free_space("/");
        $usedDisk = $totalDisk - $freeDisk;
        $metrics['disk']['total'] = round($totalDisk / 1073741824, 1) . ' GB';
        $metrics['disk']['used'] = round($usedDisk / 1073741824, 1) . ' GB';
        $metrics['disk']['percent'] = round(($usedDisk / $totalDisk) * 100);

        // Active Users (Activity in last 15 mins)
        $metrics['active_users'] = ActivityLog::where('created_at', '>=', now()->subMinutes(15))
            ->distinct('user_id')
            ->count('user_id');

        return $metrics;
    }

    /**
     * Display the system error logs (laravel.log).
     */
    public function systemLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = "";

        if (File::exists($logPath)) {
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            $startLine = max(0, $totalLines - 500);
            
            $file->seek($startLine);
            while (!$file->eof()) {
                $logs .= $file->current();
                $file->next();
            }
        }

        return view('admin.monitoring.system', compact('logs'));
    }

    /**
     * Clear the system log file.
     */
    public function clearSystemLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, "");
        }
        return redirect()->back()->with('success', 'System log cleared successfully.');
    }
}
