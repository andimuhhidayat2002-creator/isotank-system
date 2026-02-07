
@extends('layouts.admin')

@section('title', 'Predictive Vacuum Analysis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-white"><i class="bi bi-graph-up-arrow me-2 text-danger"></i>Vacuum Decay Analytics</h3>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                    <td class="text-white fw-bold">{{ $item->vacuum_mtorr }}</td>
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
