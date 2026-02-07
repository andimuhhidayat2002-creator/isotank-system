
@extends('layouts.admin')

@section('title', 'Maintenance Analytics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-white"><i class="bi bi-tools me-2 text-warning"></i>Maintenance Insights</h3>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

{{-- Main Charts --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-white">Top 10 Faulty Items</h5>
            <canvas id="faultChart"></canvas>
            <small class="text-muted d-block mt-2 text-center text-uppercase">* Based on resolved job data</small>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 text-white">Top 10 "Lemon" Tanks (Most Frequent Repairs)</h5>
            <canvas id="lemonChart"></canvas>
        </div>
    </div>
</div>

<div class="glass-card p-4 mb-4">
    <h5 class="fw-bold mb-3 text-white">Monthly Repair Volume Trend</h5>
    <canvas id="volChart" height="100"></canvas>
</div>

{{-- Recent List --}}
<div class="glass-card p-4">
    <h5 class="mb-3 text-white fw-bold">Recent Completed Jobs</h5>
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0">
            <thead class="text-secondary text-uppercase smaller-header">
                <tr>
                    <th>ISO Number</th>
                    <th>Issue</th>
                    <th>Completed At</th>
                    <th>Technician</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentJobs as $job)
                <tr>
                    <td class="fw-bold">{{ $job->isotank->iso_number ?? 'N/A' }}</td>
                    <td>{{ $job->source_item }}</td>
                    <td>{{ $job->completed_at ? \Carbon\Carbon::parse($job->completed_at)->diffForHumans() : '-' }}</td>
                    <td>{{ $job->completedBy->name ?? 'System' }}</td>
                    <td><span class="badge bg-success">RESOLVED</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Faulty Items
    @if(isset($analytics['top_faults']) && count($analytics['top_faults']) > 0)
    const ctxFault = document.getElementById('faultChart').getContext('2d');
    new Chart(ctxFault, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_column($analytics['top_faults'], 'source_item')) !!},
            datasets: [{
                label: 'Occurrences',
                data: {!! json_encode(array_column($analytics['top_faults'], 'count')) !!},
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#C9CBCF', '#7E57C2', '#EC407A', '#AB47BC'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'right', labels: { color: '#ddd' } } }
        }
    });
    @endif

    // 2. Lemon Tanks
    @if(isset($analytics['lemon_tanks']) && count($analytics['lemon_tanks']) > 0)
    const ctxLemon = document.getElementById('lemonChart').getContext('2d');
    new Chart(ctxLemon, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($analytics['lemon_tanks'], 'iso_number')) !!},
            datasets: [{
                label: 'Repair Count (12M)',
                data: {!! json_encode(array_column($analytics['lemon_tanks'], 'job_count')) !!},
                backgroundColor: 'rgba(255, 159, 64, 0.7)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: { y: { grid: { color: 'rgba(255,255,255,0.1)' } }, x: { grid: { display: false } } }
        }
    });
    @endif
    
    // 3. Volume Trend
    @if(isset($analytics['trend']) && count($analytics['trend']['labels']) > 0)
    const ctxVol = document.getElementById('volChart').getContext('2d');
    new Chart(ctxVol, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['trend']['labels']) !!},
            datasets: [{
                label: 'Total Repairs',
                data: {!! json_encode($analytics['trend']['data']) !!},
                borderColor: '#4BC0C0',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.3,
                fill: true
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
