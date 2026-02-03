@extends('layouts.app')

@section('content')
<style>
    /* PREMIUM DARK THEME OVERRIDES (Command Center Style) */
    :root {
        --dark-bg: #12141a;
        --dark-card: #1c1f26;
        --dark-border: #2d323e;
        --dark-text-main: #e2e4e9;
        --dark-text-muted: #9499a6;
        
        /* Neon Accents */
        --neon-blue: #3b82f6;
        --neon-green: #10b981;
        --neon-orange: #f59e0b;
        --neon-red: #ef4444;
        --neon-purple: #8b5cf6;
        --neon-cyan: #06b6d4;
    }

    /* Override Main Container */
    .dashboard-dark-view {
        background-color: var(--dark-bg);
        color: var(--dark-text-main);
        margin: -24px; /* Counteract default padding */
        padding: 32px;
        min-height: 100vh;
        font-family: 'Inter', system-ui, sans-serif;
    }

    /* Typography */
    h1, h2, h3, h4, h5, h6 { color: var(--dark-text-main); letter-spacing: -0.5px; }
    .text-muted { color: var(--dark-text-muted) !important; }

    /* Cards */
    .glass-card {
        background: var(--dark-card);
        border: 1px solid var(--dark-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
        position: relative;
    }
    .glass-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        border-color: rgba(255,255,255,0.1);
    }

    /* KPI Cards */
    .kpi-icon-box {
        width: 56px; height: 56px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        background: rgba(255,255,255,0.03);
    }

    /* Progress Bars - Neon Style */
    .progress {
        background-color: rgba(255,255,255,0.05);
        border-radius: 4px;
        overflow: hidden;
    }
    .neon-bar {
        position: relative;
        overflow: hidden;
    }
    .neon-bar::after {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0; right: 0;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transform: skewX(-20deg) translateX(-150%);
        animation: shimmer 2s infinite;
    }
    @keyframes shimmer {
        100% { transform: skewX(-20deg) translateX(150%); }
    }

    /* Buttons */
    .btn-dark-filter {
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--dark-border);
        color: var(--dark-text-muted);
    }
    .btn-dark-filter:hover, .btn-dark-filter.active {
        background: var(--neon-blue);
        color: white;
        border-color: var(--neon-blue);
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
    }

    /* Location Cards */
    .location-stat-row {
        border-bottom: 1px solid rgba(255,255,255,0.05);
        padding-bottom: 8px;
        margin-bottom: 8px;
    }
    .location-stat-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

    /* Modal Dark Override */
    .modal-content {
        background-color: var(--dark-card) !important;
        border: 1px solid var(--dark-border) !important;
        color: var(--dark-text-main) !important;
    }
    .modal-header, .modal-footer { border-color: var(--dark-border) !important; }
    .form-control, .form-select {
        background-color: #0f1115 !important;
        border-color: var(--dark-border) !important;
        color: white !important;
    }
</style>

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
        {{-- Total Active --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Total Fleet</div>
                        <div class="display-5 fw-bold text-white">{{ $globalStats['total_active'] }}</div>
                    </div>
                    <div class="kpi-icon-box text-white" style="background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(59,130,246,0)); color: #60a5fa !important;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-primary neon-bar" style="width: 70%"></div>
                </div>
                <div class="mt-3 text-muted small">Active Units in System</div>
            </div>
        </div>

        {{-- Alerts --}}
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.calibration') }}" class="text-decoration-none">
                <div class="glass-card p-4 h-100 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold mb-1">Calibration Alerts</div>
                            <div class="display-5 fw-bold {{ $globalStats['calibration_alerts'] > 0 ? 'text-danger' : 'text-white' }}">
                                {{ $globalStats['calibration_alerts'] }}
                            </div>
                        </div>
                        <div class="kpi-icon-box" style="{{ $globalStats['calibration_alerts'] > 0 ? 'background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(239,68,68,0)); color: #f87171;' : 'background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(16,185,129,0)); color: #34d399;' }}">
                            <i class="{{ $globalStats['calibration_alerts'] > 0 ? 'bi bi-exclamation-triangle' : 'bi bi-check-circle' }}"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar {{ $globalStats['calibration_alerts'] > 0 ? 'bg-danger' : 'bg-success' }} neon-bar" style="width: 100%"></div>
                    </div>
                    <div class="mt-3 text-muted small">
                        {{ $globalStats['calibration_alerts'] > 0 ? 'Immediate Attention Required' : 'All Certificates Valid' }}
                    </div>
                </div>
            </a>
        </div>

        {{-- Maintenance --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Open Maintenance</div>
                        <div class="display-5 fw-bold text-white">{{ $globalStats['open_maintenance'] }}</div>
                    </div>
                    <div class="kpi-icon-box" style="background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0)); color: #fbbf24;">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-warning neon-bar" style="width: {{ $globalStats['open_maintenance'] > 0 ? '60%' : '0%' }}"></div>
                </div>
                <div class="mt-3 text-muted small">
                    @if(isset($globalStats['deferred_maintenance']) && $globalStats['deferred_maintenance'] > 0)
                        <span class="text-warning">{{ $globalStats['deferred_maintenance'] }} Deferred Jobs</span>
                    @else
                        Jobs In Progress
                    @endif
                </div>
            </div>
        </div>

        {{-- Inspections --}}
        <div class="col-xl-3 col-md-6">
            <div class="glass-card p-4 h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold mb-1">Pending Review</div>
                        <div class="display-5 fw-bold text-white">{{ $globalStats['open_inspections'] }}</div>
                    </div>
                    <div class="kpi-icon-box" style="background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(6,182,212,0)); color: #22d3ee;">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-info neon-bar" style="width: {{ $globalStats['open_inspections'] > 0 ? '50%' : '0%' }}"></div>
                </div>
                <div class="mt-3 text-muted small">Incoming Inspection Reports</div>
            </div>
        </div>
    </div>

    {{-- 3. SYSTEM OCCUPANCY (VISUAL BAR) --}}
    @if(!empty($fillingStatusStats))
    <div class="glass-card p-4 mb-5">
        <h5 class="fw-bold mb-4">System Occupancy</h5>
        
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

    {{-- 4. MODULE SHORTCUTS --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="glass-card p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <h5 class="fw-bold mb-0">Quick Access</h5>
                <div class="d-flex gap-3">
                    <a href="{{ route('admin.dashboard.maintenance') }}" class="btn btn-outline-light border-secondary text-muted">
                        <i class="bi bi-tools me-2 text-warning"></i> Maintenance Stats
                    </a>
                    <a href="{{ route('admin.dashboard.vacuum') }}" class="btn btn-outline-light border-secondary text-muted">
                        <i class="bi bi-speedometer2 me-2 text-info"></i> Vacuum Logs
                    </a>
                    <a href="{{ route('admin.isotanks.index') }}" class="btn btn-outline-light border-secondary text-muted">
                        <i class="bi bi-search me-2 text-primary"></i> Search Fleet
                    </a>
                </div>
            </div>
        </div>
    </div>

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
