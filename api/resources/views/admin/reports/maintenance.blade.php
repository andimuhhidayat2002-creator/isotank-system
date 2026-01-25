@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Maintenance Center</h2>
            <div class="text-muted small">Manage repairs, deferred items, and history</div>
        </div>
    </div>

    <!-- Category Filter -->
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
          <a class="nav-link {{ $category == 'all' ? 'active' : '' }}" href="{{ route('admin.reports.maintenance', ['category' => 'all']) }}">All</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T75' ? 'active' : '' }}" href="{{ route('admin.reports.maintenance', ['category' => 'T75']) }}">T75</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T11' ? 'active' : '' }}" href="{{ route('admin.reports.maintenance', ['category' => 'T11']) }}">T11</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T50' ? 'active' : '' }}" href="{{ route('admin.reports.maintenance', ['category' => 'T50']) }}">T50</a>
        </li>
    </ul>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="maintenanceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4 py-2" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                <i class="bi bi-exclamation-circle me-2 text-danger"></i>Action Required 
                <span class="badge bg-danger rounded-pill ms-2">{{ $activeJobs->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2 text-secondary" id="deferred-tab" data-bs-toggle="tab" data-bs-target="#deferred" type="button" role="tab" aria-controls="deferred" aria-selected="false">
                <i class="bi bi-pause-circle me-2"></i>Deferred 
                <span class="badge bg-secondary rounded-pill ms-2">{{ $deferredJobs->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2 text-success" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                <i class="bi bi-check-circle me-2"></i>History
            </button>
        </li>
    </ul>

    <div class="tab-content" id="maintenanceTabsContent">
        
        {{-- TAB 1: ACTIVE JOBS --}}
        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @include('admin.reports.partials.maintenance_table', ['jobs' => $activeJobs, 'tableId' => 'tableActive', 'context' => 'active'])
                </div>
            </div>
        </div>

        {{-- TAB 2: DEFERRED JOBS --}}
        <div class="tab-pane fade" id="deferred" role="tabpanel" aria-labelledby="deferred-tab">
             <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="alert alert-light border mb-3">
                         <i class="bi bi-info-circle me-2"></i>
                         <strong>Deferred Maintenance:</strong> Items approved for postponement. These do not impact immediate operations but should be monitored.
                    </div>
                    @include('admin.reports.partials.maintenance_table', ['jobs' => $deferredJobs, 'tableId' => 'tableDeferred', 'context' => 'deferred'])
                </div>
            </div>
        </div>

        {{-- TAB 3: CLOSED JOBS --}}
        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
             <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @include('admin.reports.partials.maintenance_table', ['jobs' => $closedJobs, 'tableId' => 'tableHistory', 'context' => 'closed'])
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables for all tables
    ['#tableActive', '#tableDeferred', '#tableHistory'].forEach(function(id) {
        $(id).DataTable({
            dom: 'Bfrtip',
            buttons: [ 'excelHtml5', 'pdfHtml5' ],
            pageLength: 25,
            order: [[0, 'asc']]
        });
    });
});
</script>
@endpush
@endsection
