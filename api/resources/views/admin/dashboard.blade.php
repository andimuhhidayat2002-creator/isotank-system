@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">Global Dashboard</h2>
        <div>
             {{-- Could add report button here --}}
             @if(auth()->user()->role === 'admin')
             <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal">
                <i class="bi bi-envelope-check-fill me-2"></i> Send Report
            </button>
            @endif
        </div>
    </div>

    {{-- 1) Global Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
             <div class="card shadow-sm border-left-primary h-100">
                 <div class="card-body">
                     <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Active Isotanks</div>
                     <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $globalStats['total_active'] }}</div>
                 </div>
             </div>
        </div>
        <div class="col-md-3">
             <div class="card shadow-sm border-left-danger h-100">
                 <div class="card-body">
                     <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Open Maintenance</div>
                     <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $globalStats['open_maintenance'] }}</div>
                 </div>
             </div>
        </div>
         <div class="col-md-3">
             <div class="card shadow-sm border-left-warning h-100">
                 <div class="card-body">
                     <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Open Inspections</div>
                     <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $globalStats['open_inspections'] }}</div>
                 </div>
             </div>
        </div>
         <div class="col-md-3">
             <a href="{{ route('admin.dashboard.calibration') }}" class="text-decoration-none">
                 <div class="card shadow-sm border-left-info h-100 hover-lift">
                     <div class="card-body">
                         <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Calibration Alerts</div>
                         <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $globalStats['calibration_alerts'] }}</div>
                     </div>
                 </div>
             </a>
        </div>
    </div>

    {{-- 2) Quick Navigation to Global Statistics --}}
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Global Statistics Modules</h5>
        <div class="d-flex gap-3">
            <a href="{{ route('admin.dashboard.maintenance') }}" class="btn btn-outline-danger shadow-sm">
                <i class="bi bi-tools me-2"></i> Maintenance Statistics
            </a>
            <a href="{{ route('admin.dashboard.vacuum') }}" class="btn btn-outline-info shadow-sm">
                <i class="bi bi-speedometer2 me-2"></i> Vacuum Monitoring
            </a>
            <!-- Calibration Monitoring button removed (duplicate of Top Card) -->
        </div>
    </div>

    {{-- 3) Location Breakdown --}}
    <h5 class="fw-bold text-dark mb-3">Location Breakdown (Drill-down)</h5>
    <div class="row mb-5">
        @forelse($locations as $loc)
        <div class="col-md-3 mb-4">
            <a href="{{ route('admin.dashboard.location', urlencode($loc->location)) }}" class="text-decoration-none text-dark">
                <div class="card shadow-sm h-100 border-0 hover-lift">
                    <div class="card-body text-center py-3">
                        <h4 class="card-title fw-bold mb-2">{{ $loc->location }}</h4>
                        <div class="h2 fw-bold text-primary mb-3">{{ $loc->active_count }} <span class="fs-6 text-muted font-weight-normal">Active</span></div>
                        
                        <div class="row g-2 mb-3 px-2 text-start small">
                             <div class="col-12">
                                 <strong class="text-muted text-xs d-block mb-1">OWNERS:</strong>
                                 @if(isset($ownerBreakdown[$loc->location]))
                                    @foreach($ownerBreakdown[$loc->location] as $o)
                                        <span class="badge bg-light text-dark border me-1">{{ $o->owner ?? 'Unknown' }}: {{ $o->count }}</span>
                                    @endforeach
                                 @endif
                             </div>
                             <div class="col-12 mt-1">
                                 <strong class="text-muted text-xs d-block mb-1">MANUFACTURERS:</strong>
                                 @if(isset($manufacturerBreakdown[$loc->location]))
                                    @foreach($manufacturerBreakdown[$loc->location] as $m)
                                        <span class="badge bg-light text-dark border me-1">{{ $m->manufacturer ?? 'Unknown' }}: {{ $m->count }}</span>
                                    @endforeach
                                 @endif
                             </div>
                        </div>

                        <div class="row g-0 pt-2 border-top">
                            <div class="col-6 border-end">
                                <div class="text-success fw-bold">{{ $loc->filled_count }}</div>
                                <div class="text-xs text-muted">FILLED</div>
                            </div>
                            <div class="col-6">
                                <div class="text-secondary fw-bold">{{ $loc->empty_count }}</div>
                                <div class="text-xs text-muted">EMPTY</div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">No active isotanks found in any location.</div>
        </div>
        @endforelse
    </div>

    {{-- 3.5) Filling Status Breakdown --}}
    @if(!empty($fillingStatusStats))
    <h5 class="fw-bold text-dark mb-3">Filling Status Breakdown</h5>
    <div class="row mb-5">
        @foreach($fillingStatusStats as $stat)
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body text-center py-3">
                    <div class="mb-2">
                        @php
                            $colors = [
                                'ongoing_inspection' => '#9E9E9E',
                                'ready_to_fill' => '#4CAF50',
                                'filled' => '#2196F3',
                                'under_maintenance' => '#FF9800',
                                'waiting_team_calibration' => '#FFC107',
                                'class_survey' => '#9C27B0',
                                'no_status' => '#9E9E9E'
                            ];
                            $color = $colors[$stat['code']] ?? '#6c757d';
                        @endphp
                        <div class="badge" style="background-color: {{ $color }}; font-size: 1.5rem; padding: 0.5rem 1rem;">
                            {{ $stat['count'] }}
                        </div>
                    </div>
                    <div class="small fw-bold text-muted">{{ $stat['description'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- 4) Global Alerts (Top 5) --}}
    <div class="row">
         <div class="col-md-6 mb-4">
             <div class="card shadow-sm h-100">
                 <div class="card-header bg-white font-weight-bold text-danger">
                     Global Vacuum Alerts (Top 5)
                 </div>
                 <div class="card-body p-0">
                     <table class="table table-striped mb-0">
                         <thead>
                             <tr>
                                 <th>Isotank</th>
                                 <th>Location</th>
                                 <th>Reading/Date</th>
                             </tr>
                         </thead>
                         <tbody>
                             @forelse($vacuumAlerts as $v)
                             <tr>
                                 <td class="fw-bold">{{ $v->isotank->iso_number }}</td>
                                 <td>{{ $v->isotank->location }}</td>
                                 <td class="text-danger fw-bold">
                                     @if($v->vacuum_mtorr > 8)
                                        {{ (float)$v->vacuum_mtorr }} mTorr
                                     @else
                                        {{ $v->last_measurement_at->format('Y-m-d') }} (Expired)
                                     @endif
                                 </td>
                             </tr>
                             @empty
                             <tr><td colspan="3" class="text-center text-muted p-3">No active vacuum alerts</td></tr>
                             @endforelse
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>

         <div class="col-md-6 mb-4">
             <div class="card shadow-sm h-100">
                 <a href="{{ route('admin.dashboard.calibration') }}" class="text-decoration-none">
                     <div class="card-header bg-white font-weight-bold text-warning d-flex justify-content-between align-items-center">
                         <span>Global Calibration Alerts (Top 5)</span>
                         <i class="bi bi-arrow-right"></i>
                     </div>
                 </a>
                 <div class="card-body p-0">
                     <table class="table table-striped mb-0">
                         <thead>
                             <tr>
                                 <th>Isotank</th>
                                 <th>Component</th>
                                 <th>Due Date</th>
                             </tr>
                         </thead>
                         <tbody>
                             @forelse($calibrationAlerts as $c)
                             <tr>
                                 <td class="fw-bold">{{ optional($c->isotank)->iso_number ?? 'Unknown' }}</td>
                                 <td>
                                     {{ $c->component_type }}
                                     @if($c->position_code) <div class="small text-muted">({{ $c->position_code }})</div> @endif
                                 </td>
                                 <td class="{{ ($c->expiry_date < now()) ? 'text-danger' : 'text-warning' }} fw-bold">
                                     {{ $c->expiry_date ? $c->expiry_date->format('Y-m-d') : 'N/A' }}
                                 </td>
                             </tr>
                             @empty
                             <tr><td colspan="3" class="text-center text-muted p-3">No active calibration alerts</td></tr>
                             @endforelse
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>
    </div>
</div>

<style>
    .hover-lift {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .border-left-primary { border-left: 4px solid #0d6efd; }
    .border-left-danger { border-left: 4px solid #dc3545; }
    .border-left-warning { border-left: 4px solid #ffc107; }
    .border-left-info { border-left: 4px solid #0dcaf0; }
    .text-xs { font-size: 0.8rem; }
</style>
<!-- Unified Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.reports.send_unified') }}" method="POST" id="unifiedReportForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Operations Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Type Selection -->
                    <div class="row mb-3">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="typeDaily" value="daily" checked onchange="toggleDateInput()">
                            <label class="btn btn-outline-primary w-100 py-3" for="typeDaily">
                                <i class="bi bi-calendar-day fs-3 d-block mb-1"></i>
                                Daily Report
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="typeWeekly" value="weekly" onchange="toggleDateInput()">
                            <label class="btn btn-outline-success w-100 py-3" for="typeWeekly">
                                <i class="bi bi-calendar-week fs-3 d-block mb-1"></i>
                                Weekly Report
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="dateGroup">
                        <label for="reportDate" class="form-label">Report Date</label>
                        <input type="date" class="form-control" id="reportDate" name="date" value="{{ date('Y-m-d') }}">
                        <div class="form-text text-muted">For Weekly, this date determines which 'Week' is selected.</div>
                    </div>

                    <div class="mb-3">
                        <label for="reportEmail" class="form-label">Recipient Email(s)</label>
                        <input type="text" class="form-control" id="reportEmail" name="email" value="{{ $savedEmails ?? 'admin@isotank.com' }}" required>
                        <div class="form-text">Separate multiple emails with commas.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info text-white" onclick="previewUnifiedReport()">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill me-2"></i> Generate & Send
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDateInput() {
        const isDaily = document.getElementById('typeDaily').checked;
        const note = document.querySelector('#dateGroup .form-text');
        if(isDaily) {
            note.style.display = 'none';
        } else {
            note.style.display = 'block';
            note.innerText = "Weekly Report will cover the Mon-Sun week containing this date.";
        }
    }

    function previewUnifiedReport() {
        const isDaily = document.getElementById('typeDaily').checked;
        const date = document.getElementById('reportDate').value;
        let url = "";

        if(isDaily) {
            url = "{{ route('admin.reports.daily.preview') }}?date=" + date;
        } else {
            // Weekly Preview
            url = "{{ route('admin.reports.weekly.preview') }}?date=" + date;
        }
        window.open(url, '_blank');
    }
</script>

@endsection
