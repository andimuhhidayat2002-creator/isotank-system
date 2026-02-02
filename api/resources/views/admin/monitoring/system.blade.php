@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">System Error Logs</h2>
            <p class="text-muted small">Viewing the last 500 lines of <code>laravel.log</code>.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.monitoring.system.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear the system log file? This cannot be undone.')">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Clear Log File
                </button>
            </form>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Activity Logs
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
            <span class="small fw-bold"><i class="bi bi-file-text me-2"></i> /storage/logs/laravel.log</span>
            <button class="btn btn-sm btn-outline-light border-0" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div class="card-body p-0 bg-dark">
            <pre class="mb-0 p-3 text-white-50 small" style="height: 600px; overflow-y: scroll; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 0.8rem; line-height: 1.5;">@if(empty($logs))
[SYSTEM] Log file is currently empty or does not exist.
@else
{{ $logs }}
@endif</pre>
        </div>
    </div>

    <div class="mt-3 text-end">
        <small class="text-muted">Tip: Use <code>Ctrl+F</code> to search for specific error keywords like "Exception" or "Error".</small>
    </div>
@endsection
