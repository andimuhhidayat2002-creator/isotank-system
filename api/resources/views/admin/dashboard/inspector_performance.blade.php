
@extends('layouts.app')

@section('title', 'Inspector Performance Analytics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-white"><i class="bi bi-person-badge me-2 text-primary"></i>Inspector Performance</h3>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-white">Inspection Volume (Last 6 Months)</h5>
            <canvas id="volumeChart"></canvas>
            <small class="text-muted d-block mt-2 text-center">* Total inspections performed</small>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-white">Monthly Inspection Trend</h5>
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<div class="glass-card p-4">
    <h5 class="fw-bold mb-3 text-white">Recent Inspections</h5>
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0" id="recentInspectionsTable">
            <thead class="text-secondary text-uppercase smaller-header">
                <tr>
                    <th>ISO Number</th>
                    <th>Inspector</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentInspections as $inspection)
                <tr>
                    <td class="fw-bold">{{ optional($inspection->isotank)->iso_number ?? 'N/A' }}</td>
                    <td><span class="badge bg-primary bg-opacity-20 text-primary">Unknown (Fixing)</span></td>
                    <td>{{ optional($inspection->created_at)->format('d M Y') ?? '-' }}</td>
                    <td>{{ $inspection->inspection_type ?? 'General' }}</td>
                    <td>
                        <a href="{{ route('admin.inspection-logs.show', $inspection->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No recent inspections found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Volume Chart
    @if(isset($analytics['volume']) && count($analytics['volume']['labels']) > 0)
    const ctxVol = document.getElementById('volumeChart').getContext('2d');
    new Chart(ctxVol, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['volume']['labels']) !!},
            datasets: [{
                label: 'Inspections',
                data: {!! json_encode($analytics['volume']['data']) !!},
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
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
    @if(isset($analytics['trend']) && count($analytics['trend']['labels']) > 0)
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['trend']['labels']) !!},
            datasets: [{
                label: 'Total Inspections',
                data: {!! json_encode($analytics['trend']['data']) !!},
                borderColor: '#10B981',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(16, 185, 129, 0.1)'
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
