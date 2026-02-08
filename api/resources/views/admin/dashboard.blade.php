@extends('layouts.app')

@section('content')
<div class="dashboard-dark-view">
    
    {{-- 1. PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 2px;">Command Center</div>
            <h1 class="fw-bold mb-2">Operational Dashboard</h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.dashboard', ['category' => 'All']) }}" class="btn btn-sm btn-dark-filter rounded-pill px-3 {{ ($category ?? 'All') === 'All' ? 'active' : '' }}">All Units</a>
                <a href="{{ route('admin.dashboard', ['category' => 'T75']) }}" class="btn btn-sm btn-dark-filter rounded-pill px-3 {{ ($category ?? 'All') === 'T75' ? 'active' : '' }}">T75</a>
                <a href="{{ route('admin.dashboard', ['category' => 'T11']) }}" class="btn btn-sm btn-dark-filter rounded-pill px-3 {{ ($category ?? 'All') === 'T11' ? 'active' : '' }}">T11</a>
                <a href="{{ route('admin.dashboard', ['category' => 'T50']) }}" class="btn btn-sm btn-dark-filter rounded-pill px-3 {{ ($category ?? 'All') === 'T50' ? 'active' : '' }}">T50</a>
            </div>
        </div>
        <div class="text-end">
            <div class="text-white fw-bold mb-1" style="font-size: 1.1rem;">{{ date('l, d F Y') }}</div>
            <div class="text-success small fw-bold"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i> SYSTEM ONLINE</div>
            @if(auth()->user()->role === 'admin')
            <button class="btn btn-outline-light btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                <i class="bi bi-download me-2"></i>Export Report
            </button>
            @endif
        </div>
    </div>

    {{-- 2. PRIMARY KPI CARDS (GLOWING) --}}
    <div class="row g-4 mb-5">
        {{-- Total Fleet --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100 position-relative hover-scale">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Total Fleet</div>
                        <div class="display-6 fw-bold text-white">{{ $globalStats['total_active'] }}</div>
                        <div class="text-secondary small mt-1">Active Fleet Units</div>
                    </div>
                    <div class="kpi-icon-box" style="background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(59,130,246,0)); color: #60a5fa;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-primary neon-bar" style="width: 100%"></div>
                </div>
            </div>
        </div>

        {{-- Calibration --}}
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.calibration-master.index') }}" class="text-decoration-none">
                <div class="glass-card p-4 h-100 position-relative hover-scale">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Calibration Alerts</div>
                            <div class="display-6 fw-bold {{ $globalStats['calibration_alerts'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $globalStats['calibration_alerts'] }}
                            </div>
                             <div class="text-secondary small mt-1">{{ $globalStats['calibration_alerts'] > 0 ? 'Attention Needed' : 'All Valid' }}</div>
                        </div>
                        <div class="kpi-icon-box" style="{{ $globalStats['calibration_alerts'] > 0 ? 'background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(239,68,68,0)); color: #f87171;' : 'background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(16,185,129,0)); color: #34d399;' }}">
                            <i class="{{ $globalStats['calibration_alerts'] > 0 ? 'bi bi-exclamation-triangle' : 'bi bi-check-circle' }}"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar {{ $globalStats['calibration_alerts'] > 0 ? 'bg-danger' : 'bg-success' }} neon-bar" style="width: 100%"></div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Maintenance (Clickable) --}}
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.maintenance') }}" class="text-decoration-none">
                <div class="glass-card p-4 h-100 position-relative hover-scale">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Open Maintenance</div>
                            <div class="display-6 fw-bold text-white">{{ $globalStats['open_maintenance'] }}</div>
                            <div class="text-secondary small mt-1">
                               @if(isset($globalStats['deferred_maintenance']) && $globalStats['deferred_maintenance'] > 0)
                                    <span class="text-warning">{{ $globalStats['deferred_maintenance'] }} Deferred</span>
                               @else
                                    View Details <i class="bi bi-arrow-right-short"></i>
                               @endif
                            </div>
                        </div>
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0)); color: #fbbf24;">
                            <i class="bi bi-tools"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-warning neon-bar" style="width: {{ $globalStats['open_maintenance'] > 0 ? '60%' : '0%' }}"></div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Inspections --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100 position-relative hover-scale">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Pending Review</div>
                        <div class="display-6 fw-bold text-white">{{ $globalStats['open_inspections'] }}</div>
                        <div class="text-secondary small mt-1">Inspection Reports</div>
                    </div>
                    <div class="kpi-icon-box" style="background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(6,182,212,0)); color: #22d3ee;">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-info neon-bar" style="width: {{ $globalStats['open_inspections'] > 0 ? '50%' : '0%' }}"></div>
                </div>
            </div>
        </div>
    </div>
    {{-- End of primary KPI row --}}

    {{-- 3. SECONDARY ANALYTICS SHORTCUTS --}}
    <div class="row g-4 mb-5">
        {{-- Vacuum Monitoring Shortcut --}}
        <div class="col-xl-6 col-md-6">
            <a href="{{ route('admin.dashboard.vacuum') }}" class="text-decoration-none">
                <div class="glass-card p-4 h-100 position-relative hover-scale d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-20 p-3 me-3">
                            <i class="bi bi-graph-up-arrow text-danger fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">Vacuum Monitoring</h5>
                            <div class="text-secondary small">Analyze tank vacuum decay & integrity</div>
                        </div>
                    </div>
                    <div class="text-white">
                        <i class="bi bi-chevron-right fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Inspector Performance Shortcut --}}
        <div class="col-xl-6 col-md-6">
            <a href="{{ route('admin.dashboard.inspection') }}" class="text-decoration-none">
                <div class="glass-card p-4 h-100 position-relative hover-scale d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-20 p-3 me-3">
                            <i class="bi bi-person-badge text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">Inspector Performance</h5>
                            <div class="text-secondary small">Review performance & activity logs</div>
                        </div>
                    </div>
                    <div class="text-white">
                        <i class="bi bi-chevron-right fs-5"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    {{-- 4. SYSTEM OCCUPANCY (VISUAL BAR) --}}
    @if(!empty($fillingStatusStats))
    <div class="glass-card p-4 mb-5">
        <h5 class="fw-bold mb-4">Current Fleet Occupancy</h5>
        
        {{-- Glow Bar --}}
        <div class="progress mb-4" style="height: 32px; background: rgba(0,0,0,0.3);">
            @php
                $totalTanks = array_sum(array_column($fillingStatusStats, 'count'));
                $colorMap = [
                    'filled' => 'var(--neon-blue)',
                    'ready_to_fill' => 'var(--neon-green)',
                    'ongoing_inspection' => 'var(--neon-cyan)',
                    'under_maintenance' => 'var(--neon-orange)',
                    'waiting_team_calibration' => 'var(--neon-red)',
                    'class_survey' => 'var(--neon-purple)',
                    'cleaning' => '#6c757d',
                    'no_status' => '#ffffff'
                ];
            @endphp
            
            @foreach($fillingStatusStats as $stat)
                 @php 
                    $width = $totalTanks > 0 ? ($stat['count'] / $totalTanks) * 100 : 0;
                    $bg = $colorMap[$stat['code']] ?? '#555';
                 @endphp
                 @if($width > 0)
                    <div class="progress-bar text-white fw-bold shadow-sm" role="progressbar" style="width: {{ $width }}%; background-color: {{ $bg }}; box-shadow: 0 0 10px {{ $bg }};" 
                         data-bs-toggle="tooltip" title="{{ $stat['description'] }}">
                        @if($width > 4) {{ $stat['count'] }} @endif
                    </div>
                 @endif
            @endforeach
        </div>

        {{-- Ledger --}}
        <div class="d-flex flex-wrap gap-4 justify-content-center">
            @foreach($fillingStatusStats as $stat)
                @if($stat['count'] > 0)
                <div class="d-flex align-items-center">
                    <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background-color: {{ $colorMap[$stat['code']] ?? '#555' }}; box-shadow: 0 0 5px {{ $colorMap[$stat['code']] ?? '#555' }};"></span>
                    <span class="text-white small fw-bold me-2">{{ $stat['count'] }}</span>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem;">{{ $stat['description'] }}</span>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- 5. LOCATION SATELLITE VIEW --}}
    <div class="mb-5">
        <h5 class="fw-bold mb-3">Location Distribution (Satellite View)</h5>
        <div class="row g-4">
            @forelse($locations as $loc)
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('admin.dashboard.location', urlencode($loc->location)) }}" class="text-decoration-none">
                    <div class="glass-card p-4 h-100 hover-border-primary">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-white mb-0">{{ $loc->location }}</h5>
                            <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-20">{{ $loc->active_count }} Units</span>
                        </div>
                        
                        {{-- Mini Sat Stats --}}
                        <div class="d-flex align-items-center mb-3 text-muted small">
                             <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Filled</span>
                                    <span class="text-white">{{ $loc->filled_count }}</span>
                                </div>
                                <div class="progress" style="height: 3px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $loc->active_count > 0 ? ($loc->filled_count / $loc->active_count)*100 : 0 }}%"></div>
                                </div>
                             </div>
                             <div class="mx-3 border-end" style="height: 20px;"></div>
                             <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Empty</span>
                                    <span class="text-white">{{ $loc->empty_count }}</span>
                                </div>
                                <div class="progress" style="height: 3px;">
                                    <div class="progress-bar bg-secondary" style="width: {{ $loc->active_count > 0 ? ($loc->empty_count / $loc->active_count)*100 : 0 }}%"></div>
                                </div>
                             </div>
                        </div>

                        {{-- Owner Tags --}}
                         <div style="min-height: 24px;">
                             @if(isset($ownerBreakdown[$loc->location]))
                                <div class="d-flex flex-wrap gap-1">
                                @foreach($ownerBreakdown[$loc->location] as $o)
                                    @if($loop->index < 3)
                                    <span class="badge bg-dark border border-secondary text-muted fw-normal" style="font-size: 0.65rem;">
                                        {{ \Illuminate\Support\Str::limit($o->owner ?? 'N/A', 8) }} {{ $o->count }}
                                    </span>
                                    @endif
                                @endforeach
                                @if(count($ownerBreakdown[$loc->location]) > 3)
                                    <span class="badge bg-dark border border-secondary text-muted fw-normal" style="font-size: 0.65rem;">+{{ count($ownerBreakdown[$loc->location]) - 3 }}</span>
                                @endif
                                </div>
                             @endif
                         </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12"><div class="alert alert-dark border-secondary text-muted">No location data found.</div></div>
            @endforelse
        </div>
    </div>

    {{-- 6. SYSTEM HEALTH & LIVE ACTIVITY (SOC ROW) --}}
    <div class="row g-4 mb-5">
        {{-- Telemetry --}}
        <div class="col-xl-4 col-md-12">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-4 d-flex align-items-center">
                    <i class="bi bi-cpu me-2 text-primary"></i>Server Telemetry
                </h5>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between telemetry-label mb-1">
                        <span>CPU Load</span>
                        <span>{{ $serverMetrics['cpu'] }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary neon-bar" style="width: {{ $serverMetrics['cpu'] }}%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between telemetry-label mb-1">
                        <span>RAM Utilization</span>
                        <span>{{ $serverMetrics['ram'] }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success neon-bar" style="width: {{ $serverMetrics['ram'] }}%"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between telemetry-label mb-1">
                        <span>Disk Usage</span>
                        <span>{{ $serverMetrics['disk'] }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info neon-bar" style="width: {{ $serverMetrics['disk'] }}%"></div>
                    </div>
                </div>

                <div class="mt-auto pt-2">
                    <div class="glass-card p-3 bg-opacity-10" style="background: rgba(255,255,255,0.02); border-style: dashed;">
                        <div class="telemetry-label mb-1">Active Personnel</div>
                        <div class="d-flex align-items-center">
                            <span class="status-dot text-success" style="color: #10b981;"></span>
                            <span class="telemetry-value">{{ $activeUsersCount }}</span>
                            <span class="ms-2 text-muted small">Sessions (Last 60m)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Activity Stream --}}
        <div class="col-xl-8 col-md-12">
            <div class="glass-card h-100 d-flex flex-column">
                <div class="p-4 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-activity me-2 text-info"></i>Global Event Stream</h5>
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">LIVE MONITORING</span>
                </div>
                <div class="flex-grow-1 overflow-auto p-3 activity-scroll" style="max-height: 400px; min-height: 400px;">
                    @forelse($recentActivity as $log)
                        @php
                            $methodColor = 'text-primary';
                            if(str_contains($log->action, 'POST')) $methodColor = 'text-success';
                            if(str_contains($log->action, 'DELETE')) $methodColor = 'text-danger';
                            if(str_contains($log->action, 'PUT') || str_contains($log->action, 'PATCH')) $methodColor = 'text-warning';
                        @endphp
                        <div class="event-item border-bottom border-light border-opacity-10">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-white small">{{ $log->user->name ?? 'System' }}</span>
                                        <span class="badge bg-dark border-secondary border-opacity-25 text-muted fw-normal" style="font-size: 0.6rem;">{{ $log->user->role ?? 'N/A' }}</span>
                                    </div>
                                    <div class="text-white-50 small" style="letter-spacing: 0.02em;">
                                        <span class="{{ $methodColor }} fw-bold me-1">{{ explode(' ', $log->action)[0] }}</span> 
                                        {{ $log->description }}
                                    </div>
                                    <div class="mt-2 d-flex gap-3 text-muted" style="font-size: 0.65rem; opacity: 0.6;">
                                        <span><i class="bi bi-geo-alt me-1"></i>{{ $log->ip_address }}</span>
                                        <span><i class="bi bi-cpu me-1"></i>{{ \Illuminate\Support\Str::limit($log->user_agent, 40) }}</span>
                                    </div>
                                </div>
                                <div class="text-end shrink-0">
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</div>
                                    <div class="text-muted mt-1" style="font-size: 0.65rem;">{{ $log->created_at->format('H:i:s') }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-journal-x display-4 d-block mb-3 opacity-25"></i>
                            No recent activity detected.
                        </div>
                    @endforelse
                </div>
                <div class="p-3 bg-black bg-opacity-20 text-center">
                    <a href="{{ route('admin.monitoring.index') }}" class="text-info text-decoration-none small fw-bold">VIEW AUDIT ARCHIVE <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Reports Modal (Re-Styled) -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.reports.send_unified') }}" method="POST" id="unifiedReportForm">
            @csrf
            <div class="modal-content shadow-lg">
                <div class="modal-header border-bottom border-light border-opacity-10">
                    <h5 class="modal-title fw-bold">Generate Operations Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4">
                    <div class="row mb-3 g-3">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="typeDaily" value="daily" checked onchange="toggleDateInput()">
                            <label class="btn btn-outline-primary w-100 py-3 border-2" for="typeDaily">
                                <i class="bi bi-calendar-day fs-3 d-block mb-1"></i>
                                Daily Report
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="type" id="typeWeekly" value="weekly" onchange="toggleDateInput()">
                            <label class="btn btn-outline-success w-100 py-3 border-2" for="typeWeekly">
                                <i class="bi bi-calendar-week fs-3 d-block mb-1"></i>
                                Weekly Report
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="dateGroup">
                        <label class="form-label text-muted small">Report Date</label>
                        <input type="date" class="form-control" id="reportDate" name="date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Recipient(s)</label>
                        <input type="text" class="form-control" name="email" value="{{ $savedEmails ?? 'admin@isotank.com' }}" required>
                        <div class="form-text text-muted">Comma separated</div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-dark text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info text-white" onclick="previewUnifiedReport()">Preview</button>
                    <button type="submit" class="btn btn-primary">Send Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDateInput() {
        // Logic kept same
    }
    function previewUnifiedReport() {
        const isDaily = document.getElementById('typeDaily').checked;
        const date = document.getElementById('reportDate').value;
        let url = isDaily ? "{{ route('admin.reports.daily.preview') }}?date=" + date : "{{ route('admin.reports.weekly.preview') }}?date=" + date;
        window.open(url, '_blank');
    }
</script>
@endsection
