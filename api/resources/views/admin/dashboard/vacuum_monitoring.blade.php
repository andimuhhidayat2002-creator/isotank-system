
@extends('layouts.app')

@section('title', 'Predictive Vacuum Analysis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-white"><i class="bi bi-graph-up-arrow me-2 text-danger"></i>Vacuum Decay Analytics</h3>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

{{-- Summary Statistics Cards --}}
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small text-uppercase fw-bold mb-1">Monitored Tanks</div>
                    <div class="display-6 fw-bold text-info">{{ $analytics['summary']['total_monitored'] ?? 0 }}</div>
                </div>
                <div class="rounded-circle bg-info bg-opacity-20 p-3">
                    <i class="bi bi-eye text-info fs-4"></i>
                </div>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-info" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small text-uppercase fw-bold mb-1">Avg Rise Rate</div>
                    <div class="h3 fw-bold text-warning mb-0">{{ $analytics['summary']['avg_rise_rate'] ?? 'N/A' }}</div>
                    <small class="text-muted">High rate indicates leak risk</small>
                </div>
                <div class="rounded-circle bg-warning bg-opacity-20 p-3">
                    <i class="bi bi-speedometer2 text-warning fs-4"></i>
                </div>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-warning" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small text-uppercase fw-bold mb-1">Critical Tanks</div>
                    <div class="display-6 fw-bold text-danger">{{ $analytics['summary']['critical_tanks'] ?? 0 }}</div>
                    <small class="text-muted">> 5 mTorr</small>
                </div>
                <div class="rounded-circle bg-danger bg-opacity-20 p-3">
                    <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                </div>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-danger" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small text-uppercase fw-bold mb-1">Best Manufacturer</div>
                    <div class="h3 fw-bold text-success mb-0">{{ Str::limit($analytics['summary']['best_manufacturer'] ?? 'N/A', 10) }}</div>
                    <small class="text-muted">Lowest Decay Rate</small>
                </div>
                <div class="rounded-circle bg-success bg-opacity-20 p-3">
                    <i class="bi bi-trophy text-success fs-4"></i>
                </div>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-success" style="width: 100%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Analytics Row --}}
@if(!empty($analytics) && isset($analytics['manufacturers']))
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="glass-card p-4">
            <h5 class="fw-bold mb-3 text-white">Rise Rate by Manufacturer (Fleet Performance)</h5>
            <canvas id="manufacturerChart" height="150"></canvas>
            <small class="text-muted d-block mt-2 text-center">* Average mTorr rise per day (Higher is worse)</small>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-white">Yearly Fleet Trend</h5>
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Exceed List --}}
<div class="glass-card p-4">
    <h5 class="fw-bold mb-3 text-white">Critical Vacuum Levels (> 5 mTorr)</h5>
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0" id="vacuumExceedTable">
            <thead class="text-secondary text-uppercase smaller-header">
                <tr>
                    <th>ISO Number</th>
                    <th>Location</th>
                    <th>Vacuum (mTorr)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exceedList as $item)
                <tr>
                    <td class="fw-bold">{{ $item->isotank->iso_number ?? 'N/A' }}</td>
                    <td>{{ $item->isotank->location ?? '-' }}</td>
                    <td class="text-white fw-bold">{{ (float)$item->vacuum_mtorr }}</td>
                    <td>
                        @if($item->vacuum_mtorr > 8) <span class="badge bg-danger">CRITICAL</span>
                        @else <span class="badge bg-warning text-dark">WARNING</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.isotanks.show', $item->isotank_id) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> View History
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No high vacuum readings detected.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Manufacturer Chart
    @if(isset($analytics['manufacturers']) && count($analytics['manufacturers']['labels']) > 0)
    const ctxManu = document.getElementById('manufacturerChart').getContext('2d');
    new Chart(ctxManu, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['manufacturers']['labels']) !!},
            datasets: [{
                label: 'Avg Rise Rate (mTorr/Day)',
                data: {!! json_encode($analytics['manufacturers']['data']) !!},
                backgroundColor: 'rgba(239, 83, 80, 0.6)',
                borderColor: 'rgba(239, 83, 80, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { color: 'rgba(255,255,255,0.1)' } } }
        }
    });
    @endif

    // 2. Trend Chart
    @if(isset($analytics['yearly_trend']) && count($analytics['yearly_trend']['labels']) > 0)
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['yearly_trend']['labels']) !!},
            datasets: [{
                label: 'Avg Fleet Vacuum',
                data: {!! json_encode($analytics['yearly_trend']['data']) !!},
                borderColor: '#4FC3F7',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(79, 195, 247, 0.1)'
            }]
        },
        options: {
            scales: { y: { grid: { color: 'rgba(255,255,255,0.1)' } } }
        }
    });
    @endif
});
</script>
@endsection
