@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            <h2 class="fw-bold mt-2">Maintenance Statistics</h2>
        </div>
    </div>

    {{-- Status Distribution --}}
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm border-left-danger h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-danger">{{ $statusDistrib['open'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-danger text-uppercase">Open Jobs</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-warning">{{ $statusDistrib['on_progress'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-warning text-uppercase">On Progress</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body text-center">
                    <h1 class="display-4 fw-bold text-success">{{ $statusDistrib['closed'] ?? 0 }}</h1>
                    <div class="text-xs font-weight-bold text-success text-uppercase">Closed Jobs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Most Frequent Failures --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Frequent Failed Items</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($frequentFailures as $fail)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ ucfirst(str_replace('_', ' ', $fail->source_item)) }}
                                <span class="badge bg-primary rounded-pill">{{ $fail->count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No failures recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Maintenance by Location --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-info">Maintenance Activity by Location</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th class="text-end">Total Jobs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maintenanceByLocation as $loc)
                            <tr>
                                <td class="fw-bold">{{ $loc->location }}</td>
                                <td class="text-end font-weight-bold">{{ $loc->count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Maintenance Count per Isotank --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-dark">Top Isotanks by Maintenance Frequency (Top 20)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Isotank Number</th>
                            <th>Current Location</th>
                            <th>Total Maintenance Jobs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($maintenancePerIsotank as $iso)
                        <tr>
                            <td class="fw-bold text-primary">{{ $iso->isotank->iso_number }}</td>
                            <td>{{ $iso->isotank->location }}</td>
                            <td class="fw-bold">{{ $iso->count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #0d6efd; }
.border-left-danger { border-left: 4px solid #dc3545; }
.border-left-warning { border-left: 4px solid #ffc107; }
.border-left-success { border-left: 4px solid #198754; }
.text-xs { font-size: 0.8rem; }
</style>
@endsection
