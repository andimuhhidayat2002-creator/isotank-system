@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">Calibration Master</h2>
            <p class="text-muted">Manage certificates and calibration dates for all isotank components.</p>
        </div>
        <div>
            <a href="{{ route('admin.calibration-master.export') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Export Full Data (CSV)
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import Excel
            </button>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.calibration-master.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Calibration Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Choose Excel/CSV File</label>
                            <input type="file" name="file" class="form-control" required accept=".csv, .xlsx, .xls">
                            <small class="text-muted">Format must match the Export structure (ISOs + Components).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload & Process</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Review -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.calibration-master.index') }}" method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Search Isotank</label>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Enter Iso Number...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Isotank Number</th>
                        <th>Location</th>
                        <th>Components</th>
                        <th>Next Expiry</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($isotanks as $tank)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $tank->iso_number }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $tank->location }}</span>
                        </td>
                        <td>
                            @if($tank->components_count > 0)
                                <span class="badge bg-info text-dark">{{ $tank->components_count }} Parts</span>
                            @else
                                <span class="badge bg-secondary">Not Set</span>
                            @endif
                        </td>
                        <td>
                            @if($tank->next_expiry)
                                {{ $tank->next_expiry->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($tank->calibration_status === 'expired')
                                <span class="badge bg-danger">EXPIRED</span>
                            @elseif($tank->calibration_status === 'valid')
                                <span class="badge bg-success">VALID</span>
                            @else
                                <span class="badge bg-secondary">NO DATA</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.calibration-master.show', $tank->id) }}" class="btn btn-sm btn-outline-primary">
                                Manage Calibration
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No isotanks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($isotanks->hasPages())
        <div class="card-footer bg-white">
            {{ $isotanks->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
