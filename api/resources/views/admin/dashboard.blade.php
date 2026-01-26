@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    
    {{-- 1. PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem; letter-spacing: -0.5px;">GLOBAL DASHBOARD</h1>
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted" style="font-size: 0.9rem;">Operational Overview – Isotank System</div>
                <div class="vr my-1 text-secondary"></div>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.dashboard', ['category' => 'All']) }}" class="btn {{ ($category ?? 'All') === 'All' ? 'btn-dark' : 'btn-outline-dark' }} px-3">All</a>
                    <a href="{{ route('admin.dashboard', ['category' => 'T75']) }}" class="btn {{ ($category ?? 'All') === 'T75' ? 'btn-dark' : 'btn-outline-dark' }} px-3">T75</a>
                    <a href="{{ route('admin.dashboard', ['category' => 'T11']) }}" class="btn {{ ($category ?? 'All') === 'T11' ? 'btn-dark' : 'btn-outline-dark' }} px-3">T11</a>
                    <a href="{{ route('admin.dashboard', ['category' => 'T50']) }}" class="btn {{ ($category ?? 'All') === 'T50' ? 'btn-dark' : 'btn-outline-dark' }} px-3">T50</a>
                </div>
            </div>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-muted border fw-normal px-3 py-2 rounded-pill">
                    <i class="bi bi-clock me-1"></i> {{ date('d M Y H:i') }}
                </span>
                @if(auth()->user()->role === 'admin')
                <button type="button" class="btn btn-outline-primary btn-sm px-3 py-2 fw-medium" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 2. PRIMARY KPI CARDS --}}
    <div class="row g-4 mb-5">
        {{-- Total Active --}}
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">Active Isotanks</div>
                            <div class="display-5 fw-bold text-dark">{{ $globalStats['total_active'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-muted">
                        <i class="bi bi-check-circle me-1"></i> Total units in system
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
            </div>
        </div>

        {{-- Alerts (Calibration) --}}
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.dashboard.calibration') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden hover-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">Calibration Alerts</div>
                                <div class="display-5 fw-bold {{ $globalStats['calibration_alerts'] > 0 ? 'text-danger' : 'text-dark' }}">
                                    {{ $globalStats['calibration_alerts'] }}
                                </div>
                            </div>
                            <div class="p-3 rounded {{ $globalStats['calibration_alerts'] > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-muted">
                            <span class="{{ $globalStats['calibration_alerts'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ $globalStats['calibration_alerts'] > 0 ? 'Expiring / Expired' : 'All Valid' }}
                            </span>
                        </div>
                    </div>
                    <div class="position-absolute bottom-0 start-0 w-100 {{ $globalStats['calibration_alerts'] > 0 ? 'bg-danger' : 'bg-success' }}" style="height: 4px;"></div>
                </div>
            </a>
        </div>

        {{-- Maintenance --}}
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">Open Maintenance</div>
                            <div class="display-5 fw-bold text-dark">{{ $globalStats['open_maintenance'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning opacity-75">
                            <i class="bi bi-tools fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs">
                        <div class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i> Action Required</div>
                        @if(isset($globalStats['deferred_maintenance']) && $globalStats['deferred_maintenance'] > 0)
                            <div class="mt-2 pt-2 border-top text-secondary" style="font-size: 0.75rem;">
                                <i class="bi bi-pause-circle me-1"></i> {{ $globalStats['deferred_maintenance'] }} Deferred (Approved)
                            </div>
                        @endif
                    </div>
                </div>
                <!-- Orange Accent for Maintenance -->
                <div class="position-absolute bottom-0 start-0 w-100" style="height: 4px; background-color: var(--accent-color);"></div>
            </div>
        </div>

        {{-- Inspections --}}
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">Open Inspections</div>
                            <div class="display-5 fw-bold text-dark">{{ $globalStats['open_inspections'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-info bg-opacity-10 text-info">
                            <i class="bi bi-clipboard-check fs-3"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-muted">
                        Pending review
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-info" style="height: 4px;"></div>
            </div>
        </div>
    </div>

    {{-- 3. MODULE SHORTCUTS --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 1rem;">Quick Access</h5>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('admin.dashboard.maintenance') }}" class="btn btn-outline-secondary px-4 py-2" style="border-color: #E5E7EB;">
                            <i class="bi bi-tools me-2"></i> Maintenance Statistics
                        </a>
                        <a href="{{ route('admin.dashboard.vacuum') }}" class="btn btn-outline-secondary px-4 py-2" style="border-color: #E5E7EB;">
                            <i class="bi bi-speedometer2 me-2"></i> Vacuum Monitoring
                        </a>
                        <a href="{{ route('admin.dashboard.calibration') }}" class="btn btn-outline-secondary px-4 py-2" style="border-color: #E5E7EB;">
                            <i class="bi bi-rulers me-2"></i> Calibration Monitor
                        </a>
                         <a href="{{ route('admin.isotanks.index') }}" class="btn btn-outline-secondary px-4 py-2" style="border-color: #E5E7EB;">
                            <i class="bi bi-search me-2"></i> Search Isotanks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. LOCATION BREAKDOWN --}}
    <div class="mb-5">
         <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Location Overview</h5>
        </div>
        
        <div class="row g-4">
            @forelse($locations as $loc)
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('admin.dashboard.location', urlencode($loc->location)) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0 fs-5">{{ $loc->location }}</h6>
                                <span class="badge bg-primary rounded-pill px-3">{{ $loc->active_count }}</span>
                            </div>

                            {{-- Owners Mini-Grid --}}
                             <div class="mb-3" style="min-height: 40px;">
                                 @if(isset($ownerBreakdown[$loc->location]))
                                    <div class="d-flex flex-wrap gap-1">
                                    @foreach($ownerBreakdown[$loc->location] as $o)
                                        <span class="badge {{ $loop->first ? 'bg-dark' : 'bg-light text-secondary border' }} fw-normal" style="font-size: 0.7rem;">
                                            {{ \Illuminate\Support\Str::limit($o->owner ?? 'N/A', 10) }} {{ $o->count }}
                                        </span>
                                    @endforeach
                                    </div>
                                 @endif
                             </div>

                             {{-- Status Bar --}}
                             <div class="d-flex rounded overflow-hidden mt-auto" style="height: 6px;">
                                 @php
                                     $total = $loc->active_count > 0 ? $loc->active_count : 1;
                                     $filledP = ($loc->filled_count / $total) * 100;
                                     $emptyP = ($loc->empty_count / $total) * 100;
                                 @endphp
                                 <div class="bg-primary" style="width: {{ $filledP }}%"></div>
                                 <div class="bg-secondary bg-opacity-25" style="width: {{ $emptyP }}%"></div>
                             </div>
                             <div class="d-flex justify-content-between mt-2" style="font-size: 0.75rem;">
                                 <span class="text-primary fw-bold">{{ $loc->filled_count }} Filled</span>
                                 <span class="text-muted">{{ $loc->empty_count }} Empty</span>
                             </div>
                        </div>
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12"><div class="alert alert-light border">No data available.</div></div>
            @endforelse
        </div>
    </div>
    
    {{-- 5. FILLING STATUS SUMMARY (Compact) --}}
    @if(!empty($fillingStatusStats))
    <div class="mb-5">
        <h5 class="fw-bold text-dark mb-3">Status Breakdown</h5>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row text-center">
                     @foreach($fillingStatusStats as $stat)
                        @php
                            $colors = ['ongoing_inspection'=>'#6B7280', 'ready_to_fill'=>'#10B981', 'filled'=>'#1F4FD8', 'under_maintenance'=>'#F97316', 'waiting_team_calibration'=>'#F59E0B', 'class_survey'=>'#8B5CF6'];
                            $c = $colors[$stat['code']] ?? '#9CA3AF';
                        @endphp
                        <div class="col border-end last-no-border">
                            <h4 class="fw-bold mb-0" style="color: {{ $c }}">{{ $stat['count'] }}</h4>
                            <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">{{ $stat['description'] }}</div>
                        </div>
                     @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- Styles Specific to Dashboard -->
<style>
    .hover-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; cursor: pointer; }
    .last-no-border:last-child { border-right: none !important; }
    .btn-outline-secondary:hover { background-color: #F3F4F6; color: #1F2937; }
</style>

<!-- Report Modal (Preserved Functionality) -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.reports.send_unified') }}" method="POST" id="unifiedReportForm">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Generate Operations Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <label class="form-label fw-bold small">Report Date</label>
                        <input type="date" class="form-control" id="reportDate" name="date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Recipient(s)</label>
                        <input type="text" class="form-control" name="email" value="{{ $savedEmails ?? 'admin@isotank.com' }}" required>
                        <div class="form-text">Comma separated</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info text-white" onclick="previewUnifiedReport()">Preview</button>
                    <button type="submit" class="btn btn-primary">Send Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDateInput() {
        const isDaily = document.getElementById('typeDaily').checked;
        const note = document.querySelector('#dateGroup .form-text'); // Might behave differently if note removed
    }
    function previewUnifiedReport() {
        const isDaily = document.getElementById('typeDaily').checked;
        const date = document.getElementById('reportDate').value;
        let url = isDaily ? "{{ route('admin.reports.daily.preview') }}?date=" + date : "{{ route('admin.reports.weekly.preview') }}?date=" + date;
        window.open(url, '_blank');
    }
</script>
@endsection
