@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            <h2 class="fw-bold mt-2">Calibration Monitoring</h2>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-success">{{ $statusSummary['valid'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-success text-uppercase">Valid Calibrations</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-danger">{{ $statusSummary['expired'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-danger text-uppercase">Expired</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-info">{{ $statusSummary['planned'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-info text-uppercase">Planned / In Progress</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
             <div class="card shadow-sm text-center h-100 bg-light">
                 <div class="card-body pt-2">
                     <h6 class="text-muted text-uppercase mb-3 mt-2 font-weight-bold">Expiring Soon</h6>
                     <div class="d-flex justify-content-around">
                        <div>
                            <div class="h4 fw-bold text-danger mb-0">{{ $expiring30 ?? 0 }}</div>
                            <small>30 Days</small>
                        </div>
                        <div>
                            <div class="h4 fw-bold text-warning mb-0">{{ $expiring60 ?? 0 }}</div>
                            <small>60 Days</small>
                        </div>
                        <div>
                            <div class="h4 fw-bold text-info mb-0">{{ $expiring90 ?? 0 }}</div>
                            <small>90 Days</small>
                        </div>
                     </div>
                 </div>
             </div>
        </div>
    </div>

    {{-- Detailed Expiring Alerts --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-left-warning">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Attention Required (Expired & Upcoming)</h6>
                    <div>
                        <a href="{{ route('admin.calibration.export_csv') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-download"></i> Download CSV
                        </a>
                        <span class="badge bg-danger ms-2">{{ $expiringAlertsDetailed->count() }} Items</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th>Expiry Date</th>
                                    <th>Days Left</th>
                                    <th>Isotank</th>
                                    <th>Location</th>
                                    <th>Component</th>
                                    <th>Serial No</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringAlertsDetailed as $item)
                                @php 
                                    $expDate = $item->expiry_date;
                                    $daysLeft = $expDate ? now()->diffInDays($expDate, false) : 999; 
                                    
                                    $rowClass = '';
                                    if($daysLeft === 999) $rowClass = '';
                                    elseif($daysLeft < 30) $rowClass = 'table-danger';
                                    elseif($daysLeft < 60) $rowClass = 'table-warning';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="fw-bold">{{ $expDate ? $expDate->format('Y-m-d') : 'N/A' }}</td>
                                    <td>
                                        @if($daysLeft === 999)
                                            <span class="badge bg-secondary">Unknown</span>
                                        @elseif($daysLeft < 0)
                                            <span class="badge bg-danger">Exp {{ abs((int)$daysLeft) }} days ago</span>
                                        @else
                                            <span class="badge {{ $daysLeft < 30 ? 'bg-danger' : 'bg-warning' }} text-dark">{{ (int)$daysLeft }} Days</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.isotanks.show', $item->isotank_id) }}" class="text-decoration-none fw-bold">
                                            {{ optional($item->isotank)->iso_number ?? 'Unknown' }}
                                        </a>
                                    </td>
                                    <td>{{ optional($item->isotank)->location ?? '-' }}</td>
                                    <td>{{ $item->component_type }} {{ $item->position_code ? "({$item->position_code})" : '' }}</td>
                                    <td>{{ $item->serial_number ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('admin.calibration-master.index', ['search' => optional($item->isotank)->iso_number]) }}" class="btn btn-xs btn-outline-primary">Manage</a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No upcoming expiries found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Rejected History --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Rejected Calibration History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Isotank</th>
                                    <th>Item</th>
                                    <th>Vendor</th>
                                    <th>Performed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rejectedHistory as $rej)
                                <tr>
                                    <td>{{ $rej->created_at->format('Y-m-d') }}</td>
                                    <td class="fw-bold">{{ optional($rej->isotank)->iso_number ?? 'Unknown' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $rej->item_name)) }}</td>
                                    <td>{{ $rej->vendor ?? '-' }}</td>
                                    <td>{{ optional($rej->performedBy)->name ?? 'Unknown' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No rejected calibrations recorded</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Vendor Breakdown --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Calibration by Vendor</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th class="text-end">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byVendor as $v)
                            <tr>
                                <td>{{ $v->vendor }}</td>
                                <td class="text-end fw-bold">{{ $v->count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
