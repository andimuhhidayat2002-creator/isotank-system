
@extends('layouts.app')

@section('title', 'Maintenance Analytics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-white"><i class="bi bi-tools me-2 text-warning"></i>Maintenance Insights</h3>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

{{-- Summary Statistics Cards --}}
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small text-uppercase fw-bold mb-1">Total Open Jobs</div>
                    <div class="display-6 fw-bold text-warning">{{ $analytics['total_open'] ?? 0 }}</div>

                </div>
                <div class="rounded-circle bg-warning bg-opacity-20 p-3">
                    <i class="bi bi-wrench text-warning fs-4"></i>
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
                    <div class="text-muted small text-uppercase fw-bold mb-1">Avg MTTR</div>
                    <div class="display-6 fw-bold text-info">{{ $analytics['avg_mttr'] ?? 'N/A' }}</div>
                    <small class="text-muted">Mean Time To Repair</small>

                </div>
                <div class="rounded-circle bg-info bg-opacity-20 p-3">
                    <i class="bi bi-clock-history text-info fs-4"></i>
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
                    <div class="text-muted small text-uppercase fw-bold mb-1">Deferred Jobs</div>
                    <div class="display-6 fw-bold text-danger">{{ $analytics['deferred'] ?? 0 }}</div>
                    <small class="text-muted">Pending Action</small>

                </div>
                <div class="rounded-circle bg-danger bg-opacity-20 p-3">
                    <i class="bi bi-pause-circle text-danger fs-4"></i>
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
                    <div class="text-muted small text-uppercase fw-bold mb-1">Completed (30d)</div>
                    <div class="display-6 fw-bold text-success">{{ $analytics['completed_30d'] ?? 0 }}</div>
                    <small class="text-muted">Last Month</small>

                </div>
                <div class="rounded-circle bg-success bg-opacity-20 p-3">
                    <i class="bi bi-check-circle text-success fs-4"></i>
                </div>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-success" style="width: 100%"></div>
            </div>
        </div>
    </div>
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
