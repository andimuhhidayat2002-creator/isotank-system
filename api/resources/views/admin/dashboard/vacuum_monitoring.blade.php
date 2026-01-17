@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            <h2 class="fw-bold mt-2">Vacuum Monitoring</h2>
        </div>
    </div>
    
    <div class="row">
        {{-- Current Exceed (>8) --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-top-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="m-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Current High Vacuum (>8 mTorr)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Isotank</th>
                                <th>Location</th>
                                <th>Reading</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($currentExceed as $ex)
                            <tr>
                                <td class="fw-bold">{{ $ex->isotank->iso_number }}</td>
                                <td>{{ $ex->isotank->location }}</td>
                                <td class="text-danger fw-bold">{{ $ex->vacuum_mtorr }}</td>
                                <td>{{ $ex->last_measurement_at->format('Y-m-d') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted p-3">No active high vacuum alerts</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Expiry Alerts (>11 months) --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100 border-top-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="m-0"><i class="bi bi-clock-history me-2"></i> Measurement Expired (>11 Months)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Isotank</th>
                                <th>Location</th>
                                <th>Last Check</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiryAlerts as $alert)
                            <tr>
                                <td class="fw-bold">{{ $alert->isotank->iso_number }}</td>
                                <td>{{ $alert->isotank->location }}</td>
                                <td class="text-danger fw-bold">{{ $alert->last_measurement_at->format('Y-m-d') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted p-3">No expiry alerts</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Suction History --}}
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white font-weight-bold">
                    Latest Vacuum Suction Activities
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                       <table class="table table-hover mb-0">
                          <thead>
                              <tr>
                                  <th>Date</th>
                                  <th>Isotank</th>
                                  <th>Before (mTorr)</th>
                                  <th>After (mTorr)</th>
                                  <th>Status</th>
                              </tr>
                          </thead>
                          <tbody>
                              @foreach($suctionHistory as $h)
                              <tr>
                                  <td>{{ $h->created_at->format('Y-m-d H:i') }}</td>
                                  <td class="fw-bold">{{ $h->isotank->iso_number }}</td>
                                  <td>{{ $h->portable_vacuum_value ?? $h->morning_vacuum_value ?? '-' }}</td>
                                  <td class="fw-bold text-success">{{ $h->evening_vacuum_value ?? $h->morning_vacuum_value ?? $h->portable_vacuum_when_machine_stops ?? '-' }}</td>
                                  <td>
                                      @if($h->completed_at)
                                        <span class="badge bg-success">Completed</span>
                                      @else
                                        <span class="badge bg-warning text-dark">In Progress</span>
                                      @endif
                                  </td>
                              </tr>
                              @endforeach
                          </tbody>
                       </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Frequent Exceeders --}}
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white font-weight-bold">
                    Most Frequent High Vacuum
                </div>
                <div class="card-body p-0">
                     <table class="table table-sm">
                         <thead>
                             <tr>
                                 <th>Isotank</th>
                                 <th class="text-end">Occurrences</th>
                             </tr>
                         </thead>
                         <tbody>
                             @foreach($exceedFrequency as $freq)
                             <tr>
                                 <td class="fw-bold">{{ $freq->isotank->iso_number }}</td>
                                 <td class="text-end">{{ $freq->count }}</td>
                             </tr>
                             @endforeach
                         </tbody>
                     </table>
                </div>
            </div>
        </div>
    </div>
    {{-- STATISTICS SECTION --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="m-0 fw-bold text-primary"><i class="bi bi-graph-up"></i> Global Vacuum Trend (Last 12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="m-0 fw-bold"><i class="bi bi-arrow-left-right"></i> Vacuum Stability Analysis (Current vs Last Year)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="comparisonTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Isotank</th>
                                    <th>Current Reading</th>
                                    <th>Date</th>
                                    <th>Last Year Reading</th>
                                    <th>Date</th>
                                    <th>Change (mTorr)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comparisonData as $row)
                                @php 
                                    $change = $row['change']; 
                                    $statusClass = '';
                                    $status = '';
                                    if ($change === null) {
                                        $status = 'No History';
                                        $statusClass = 'text-muted';
                                    } else if ($change > 2) { 
                                        $status = 'Degraded'; 
                                        $statusClass = 'text-danger'; 
                                    } else if ($change < -1) { 
                                        $status = 'Improved'; 
                                        $statusClass = 'text-success'; 
                                    } else { 
                                        $status = 'Stable'; 
                                        $statusClass = 'text-primary'; 
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $row['iso_number'] }}</td>
                                    <td>{{ number_format($row['current_val'], 1) }} mTorr</td>
                                    <td class="small text-muted">{{ $row['current_date']->format('Y-m-d') }}</td>
                                    <td>{{ $row['history_val'] ? number_format($row['history_val'], 1) . ' mTorr' : '-' }}</td>
                                    <td class="small text-muted">{{ $row['history_date'] ? $row['history_date']->format('Y-m-d') : '-' }}</td>
                                    <td class="{{ $change === null ? 'text-muted' : ($change > 0 ? 'text-danger' : 'text-success') }} fw-bold">
                                        {{ $change !== null ? ($change > 0 ? '+' : '') . number_format($change, 1) : '-' }}
                                    </td>
                                    <td><span class="badge bg-light border {{ $statusClass }}">{{ $status }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // 1. Comparison Table
        $('#comparisonTable').DataTable({
            pageLength: 10,
            order: [[5, 'desc']] // Order by Change desc (High degradation first)
        });

        // 2. Trend Chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        const trendData = @json($trendData);
        
        if (trendData.length > 0) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.month),
                    datasets: [{
                        label: 'Avg Vacuum Level (mTorr)',
                        data: trendData.map(d => d.avg_vacuum),
                        borderColor: '#0d47a1',
                        backgroundColor: 'rgba(13, 71, 161, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toFixed(2) + ' mTorr';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Vacuum (mTorr)' }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
