@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-dark">Management Reports</h2>
            <p class="text-muted">Weekly Operations Summary & Fleet Status</p>
        </div>
        <div class="col-md-6 text-end">
            <form action="{{ route('admin.reports.send') }}" method="POST" class="d-inline" onsubmit="return confirm('Send Weekly Report to ALL Admins now?')">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill"></i> Send Weekly Report Now
                </button>
            </form>
        </div>
    </div>

    <!-- PREVIEW CARDS -->
    <div class="row mb-4">
        <div class="col-md-4">
             <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h5 class="text-muted">Weekly Activity</h5>
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div>
                            <h2 class="text-primary fw-bold">{{ $stats['inspections_week'] }}</h2>
                            <small class="text-muted d-block">Inspections</small>
                        </div>
                        <div class="vr"></div>
                        <div>
                            <h2 class="text-primary fw-bold">{{ $stats['maintenance_week'] }}</h2>
                            <small class="text-muted d-block">Repairs Done</small>
                        </div>
                    </div>
                </div>
             </div>
        </div>
        
        <div class="col-md-4">
             <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h5 class="text-muted">Fleet Status</h5>
                    <div class="mt-3">
                        <h2 class="fw-bold">{{ $stats['total_fleet'] }} <small class="text-muted fs-6">Units</small></h2>
                        <small class="text-success">{{ $stats['breakdown_status']->where('code', 'ready_to_fill')->first()['count'] ?? 0 }} Ready</small> | 
                        <small class="text-warning">{{ $stats['breakdown_status']->where('code', 'under_maintenance')->first()['count'] ?? 0 }} Maint.</small>
                    </div>
                </div>
             </div>
        </div>

        <div class="col-md-4">
             <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <h5 class="text-muted">Compliance Alerts</h5>
                    <div class="mt-3">
                        @if($stats['expiry_count'] > 0)
                            <h2 class="text-danger fw-bold">{{ $stats['expiry_count'] }}</h2>
                            <small class="text-danger fw-bold">Items Expiring (30 Days)</small>
                        @else
                            <h2 class="text-success fw-bold">OK</h2>
                            <small class="text-success">No immediate expiries</small>
                        @endif
                    </div>
                </div>
             </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">Report Preview</h5>
        </div>
        <div class="card-body p-0">
            <iframe srcdoc="{{ view('emails.reports.weekly', array_merge($stats, ['date_range' => now()->startOfWeek()->format('d M') . ' - ' . now()->endOfWeek()->format('d M Y')]))->render() }}" 
                    style="width: 100%; height: 600px; border: none;"></iframe>
        </div>
    </div>
</div>
@endsection
