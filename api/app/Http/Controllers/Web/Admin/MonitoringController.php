<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MonitoringController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Standard Filters
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
        $users = \App\Models\User::orderBy('name')->get();

        return view('admin.monitoring.index', compact('logs', 'users'));
    }

    /**
     * Display the system error logs (laravel.log).
     */
    public function systemLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = "";

        if (File::exists($logPath)) {
            // Read last 500 lines for performance
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            $startLine = max(0, $totalLines - 500);
            $logs = "";
            
            $file->seek($startLine);
            while (!$file->eof()) {
                $logs .= $file->current();
                $file->next();
            }
        } else {
            $logs = "No log file found at: " . $logPath;
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
