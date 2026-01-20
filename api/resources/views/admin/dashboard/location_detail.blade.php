@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted mb-2 d-inline-block small">
                <i class="bi bi-arrow-left me-1"></i> Back to Global Dashboard
            </a>
            <h1 class="fw-bold text-dark mb-1" style="font-size: 1.75rem;">LOCATION: <span class="text-primary">{{ strtoupper($location) }}</span></h1>
            <div class="text-muted small">Asset & Operational Detail</div>
        </div>
    </div>

    {{-- A. Asset Summary Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Isotanks</div>
                            <div class="display-6 fw-bold text-dark">{{ $assetSummary['total_isotanks'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Maintenance</div>
                            <div class="display-6 fw-bold text-dark">{{ $assetSummary['open_maintenance'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning opacity-75">
                            <i class="bi bi-tools fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Calibration Exp/Due</div>
                            <div class="display-6 fw-bold {{ $assetSummary['expired_calibration'] > 0 ? 'text-warning' : 'text-dark' }}">{{ $assetSummary['expired_calibration'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">High Vacuum</div>
                            <div class="display-6 fw-bold {{ $assetSummary['high_vacuum'] > 0 ? 'text-danger' : 'text-dark' }}">{{ $assetSummary['high_vacuum'] }}</div>
                        </div>
                        <div class="p-3 rounded bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-speedometer2 fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
            </div>
        </div>
    </div>

    {{-- Filling Status Overview --}}
    <h5 class="fw-bold text-dark mb-3">Filling Status (Content)</h5>
    <div class="row mb-5">
        @php
            $statusColors = [
                'ongoing_inspection' => ['color' => '#6B7280', 'label' => 'Ongoing Inspection'],
                'ready_to_fill' => ['color' => '#10B981', 'label' => 'Ready to Fill'],
                'filled' => ['color' => '#1F4FD8', 'label' => 'Filled'],
                'under_maintenance' => ['color' => '#F97316', 'label' => 'Under Maintenance'],
                'waiting_team_calibration' => ['color' => '#F59E0B', 'label' => 'Waiting Calibration'],
                'class_survey' => ['color' => '#8B5CF6', 'label' => 'Class Survey'],
                'unspecified' => ['color' => '#9CA3AF', 'label' => 'Not Specified'],
            ];
        @endphp
        
        @foreach($fillingStats as $code => $data)
            <div class="col-md-2 mb-3">
                <div class="card shadow-sm h-100 border-0 position-relative overflow-hidden">
                    <div class="card-body text-center py-4">
                        <div class="mb-2">
                             <div class="h2 fw-bold mb-0" style="color: {{ $statusColors[$code]['color'] ?? '#6c757d' }}">
                                {{ $data['count'] }}
                            </div>
                        </div>
                        <div class="small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px; font-size: 0.7rem;">{{ $data['description'] }}</div>
                    </div>
                    <div class="position-absolute start-0 top-0 bottom-0" style="width: 4px; background-color: {{ $statusColors[$code]['color'] ?? '#6c757d' }}"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-5">
        {{-- B. Maintenance Snapshot --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold fs-5">Maintenance Snapshot</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-4 px-3">
                        <div class="text-center">
                            <div class="h4 fw-bold text-danger mb-0">{{ $maintenanceStats['open'] ?? 0 }}</div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Open</small>
                        </div>
                        <div class="text-center">
                            <div class="h4 fw-bold text-warning mb-0">{{ $maintenanceStats['on_progress'] ?? 0 }}</div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">In Progress</small>
                        </div>
                        <div class="text-center">
                             <div class="h4 fw-bold text-secondary mb-0">{{ $maintenanceStats['closed'] ?? 0 }}</div>
                            <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Closed</small>
                        </div>
                    </div>

                    <h6 class="fw-bold text-uppercase text-muted text-xs mb-3">Top Recurring Issues</h6>
                    <ul class="list-group list-group-flush">
                        @forelse($frequentFailures as $fail)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                {{ ucfirst(str_replace('_', ' ', $fail->source_item)) }}
                                <span class="badge bg-light text-dark border">{{ $fail->count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">No failures recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right Column: Vacuum & Calibration --}}
        <div class="col-lg-6">
            {{-- C. Vacuum Exceed List --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold fs-6 text-danger">High Vacuum (>8 mTorr)</h6>
                    <i class="bi bi-speedometer2 text-danger opacity-50"></i>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Isotank</th>
                                    <th>Value</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vacuumExceed as $v)
                                <tr>
                                    <td class="fw-bold ps-4 text-primary">{{ $v->isotank->iso_number }}</td>
                                    <td class="text-danger fw-bold">{{ (float)$v->vacuum_mtorr }} mTorr</td>
                                    <td class="text-muted small">{{ $v->last_measurement_at->format('Y-m-d') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No active isotanks exceeding 8 mTorr</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- D. Calibration Snapshot --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold fs-6 text-warning">Calibration Due (< 90 Days)</h6>
                    <i class="bi bi-rulers text-warning opacity-50"></i>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                         <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Isotank</th>
                                    <th>Item</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringCalibration as $c)
                                <tr>
                                    <td class="fw-bold ps-4 text-primary">{{ $c->isotank->iso_number }}</td>
                                    <td class="small">{{ ucfirst(str_replace('_', ' ', $c->item_name)) }}</td>
                                    <td>
                                        <span class="badge {{ $c->valid_until < now()->addMonth() ? 'bg-danger' : 'bg-warning text-dark' }}">
                                            {{ $c->valid_until->format('Y-m-d') }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">All calibrations valid</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Detailed Isotank List & Activities --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                     <h6 class="m-0 fw-bold fs-5 text-dark">Isotanks Inventory: {{ $location }}</h6>
                     <a href="{{ route('admin.dashboard.location.export', urlencode($location)) }}" class="btn btn-sm btn-success text-white fw-bold">
                        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
                     </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle" id="isotankTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ISO Number</th>
                                    <th>Status</th>
                                    <th>Filling Status</th>
                                    <th>Description</th>
                                    <th>Capacity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($isotankList as $iso)
                                <tr>
                                    <td class="fw-bold ps-4 text-primary">{{ $iso->iso_number }}</td>
                                    <td>
                                        <span class="badge rounded-pill {{ $iso->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($iso->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($iso->filling_status_code)
                                            <span class="badge bg-light text-dark border">{{ $iso->filling_status_code }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $iso->filling_status_desc ?? '-' }}</td>
                                    <td class="small text-muted">{{ $iso->capacity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                     <h6 class="m-0 fw-bold fs-5 text-dark">Recent Activities</h6>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                             <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>ISO Number</th>
                                    <th>Activity Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities as $act)
                                <tr>
                                    <td class="ps-4 small text-muted">{{ $act->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="fw-bold text-primary">{{ $act->isotank->iso_number }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $act->activity_type)) }}</td>
                                    <td>
                                        <span class="badge {{ $act->status == 'done' || $act->status == 'closed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ ucfirst($act->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No recent activities found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
