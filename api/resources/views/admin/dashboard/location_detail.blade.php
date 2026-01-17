@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            <h2 class="fw-bold mt-2">Location: <span class="text-primary">{{ $location }}</span></h2>
        </div>
    </div>

    {{-- A. Asset Summary --}}
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Isotanks</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $assetSummary['total_isotanks'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Open Maintenance</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $assetSummary['open_maintenance'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Expired Calibration</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $assetSummary['expired_calibration'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">High Vacuum (>8 mTorr)</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $assetSummary['high_vacuum'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filling Status Overview --}}
    <h5 class="fw-bold text-dark mb-3">Filling Status (Content)</h5>
    <div class="row mb-5">
        @php
            $statusColors = [
                'ongoing_inspection' => ['color' => '#9E9E9E', 'label' => 'Ongoing Inspection'],
                'ready_to_fill' => ['color' => '#4CAF50', 'label' => 'Ready to Fill'],
                'filled' => ['color' => '#2196F3', 'label' => 'Filled'],
                'under_maintenance' => ['color' => '#FF9800', 'label' => 'Under Maintenance'],
                'waiting_team_calibration' => ['color' => '#FFC107', 'label' => 'Waiting Calibration'],
                'class_survey' => ['color' => '#9C27B0', 'label' => 'Class Survey'],
                'unspecified' => ['color' => '#9E9E9E', 'label' => 'Not Specified'],
            ];
        @endphp
        
        @foreach($fillingStats as $code => $data)
            <div class="col-md-2 mb-3">
                <div class="card shadow-sm h-100 border-0" style="border-left: 4px solid {{ $statusColors[$code]['color'] ?? '#6c757d' }} !important;">
                    <div class="card-body text-center py-3">
                        <div class="mb-2">
                            <div class="badge" style="background-color: {{ $statusColors[$code]['color'] ?? '#6c757d' }}; font-size: 1.5rem; padding: 0.5rem 1rem;">
                                {{ $data['count'] }}
                            </div>
                        </div>
                        <div class="small fw-bold text-muted">{{ $data['description'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        {{-- B. Maintenance Snapshot --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Snapshot</h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Status Distribution</h6>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Open
                            <span class="badge bg-danger rounded-pill">{{ $maintenanceStats['open'] ?? 0 }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            On Progress
                            <span class="badge bg-warning text-dark rounded-pill">{{ $maintenanceStats['on_progress'] ?? 0 }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Closed (Total)
                            <span class="badge bg-secondary rounded-pill">{{ $maintenanceStats['closed'] ?? 0 }}</span>
                        </li>
                    </ul>

                    <h6 class="font-weight-bold">Most Frequent Failures (Top 5)</h6>
                    <ul class="list-group">
                        @forelse($frequentFailures as $fail)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ ucfirst(str_replace('_', ' ', $fail->source_item)) }}
                                <span class="badge bg-secondary rounded-pill">{{ $fail->count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No failures recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right Column: Vacuum & Calibration --}}
        <div class="col-lg-6 mb-4">
            
            {{-- C. Vacuum Exceed List --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom-danger">
                    <h6 class="m-0 font-weight-bold text-danger">High Vacuum List (>8 mTorr)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Isotank</th>
                                    <th>Value</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vacuumExceed as $v)
                                <tr>
                                    <td class="fw-bold">{{ $v->isotank->iso_number }}</td>
                                    <td class="text-danger fw-bold">{{ (float)$v->vacuum_mtorr }} mTorr</td>
                                    <td>{{ $v->last_measurement_at->format('Y-m-d') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No active isotanks exceeding 8 mTorr</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- D. Calibration Snapshot --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 border-bottom-warning">
                    <h6 class="m-0 font-weight-bold text-warning">Calibration Due (< 90 Days)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Isotank</th>
                                    <th>Type</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringCalibration as $c)
                                <tr>
                                    <td class="fw-bold">{{ $c->isotank->iso_number }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $c->item_name)) }}</td>
                                    <td class="fw-bold {{ $c->valid_until < now()->addMonth() ? 'text-danger' : 'text-warning' }}">
                                        {{ $c->valid_until->format('Y-m-d') }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No upcoming calibrations</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- Detailed Isotank List & Activities --}}
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                     <h6 class="m-0 font-weight-bold text-primary">Isotanks at Location: {{ $location }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="isotankTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ISO Number</th>
                                    <th>Status</th>
                                    <th>Filling Status</th>
                                    <th>Filling Desc</th>
                                    <th>Capacity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($isotankList as $iso)
                                <tr>
                                    <td class="fw-bold">{{ $iso->iso_number }}</td>
                                    <td>
                                        <span class="badge {{ $iso->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($iso->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($iso->filling_status_code)
                                            <span class="badge bg-info text-dark">{{ $iso->filling_status_code }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $iso->filling_status_desc ?? '-' }}</td>
                                    <td>{{ $iso->capacity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                     <h6 class="m-0 font-weight-bold text-secondary">Recent Activities at {{ $location }}</h6>
                </div>
                <div class="card-body">
                     <div class="table-responsive">
                        <table class="table table-sm">
                             <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>ISO</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities as $act)
                                <tr>
                                    <td>{{ $act->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="fw-bold">{{ $act->isotank->iso_number }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $act->activity_type)) }}</td>
                                    <td>
                                        <span class="badge {{ $act->status == 'done' || $act->status == 'closed' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($act->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No recent activities found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-danger { border-left: 4px solid #dc3545; }
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-info { border-left: 4px solid #0dcaf0; }
.border-left-success { border-left: 4px solid #198754; }
.border-left-secondary { border-left: 4px solid #6c757d; }
.border-left-dark { border-left: 4px solid #212529; }
.text-xs { font-size: 0.8rem; }
</style>
@endsection
