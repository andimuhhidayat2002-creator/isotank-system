@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Maintenance Detail: {{ $job->isotank->iso_number }}</h2>
    <a href="{{ route('admin.reports.maintenance') }}" class="btn btn-secondary">Back to List</a>
</div>

<div class="row">
    <div class="col-md-4">
        <!-- Status Card -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Status & Assignment</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Status</th><td>
                        @if($job->status === 'closed')
                            <span class="badge bg-success">CLOSED</span>
                        @elseif($job->status === 'open')
                            <span class="badge bg-danger">OPEN</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ strtoupper(str_replace('_', ' ', $job->status)) }}</span>
                        @endif
                    </td></tr>
                    <tr><th>Priority</th><td><span class="badge bg-{{ $job->priority === 'high' ? 'danger' : ($job->priority === 'medium' ? 'warning text-dark' : 'info') }}">{{ strtoupper($job->priority) }}</span></td></tr>
                    <tr><th>Assigned To</th><td>{{ $job->assignee->name ?? '-' }}</td></tr>
                    <tr><th>Completed By</th><td>{{ $job->completedBy->name ?? '-' }}</td></tr>
                    <tr><th>Completed At</th><td>{{ $job->completed_at ? $job->completed_at->format('Y-m-d H:i') : '-' }}</td></tr>
                </table>
            </div>
        </div>

        <!-- Issue Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Issue Details</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Source Item</th><td class="fw-bold text-uppercase">{{ str_replace('_', ' ', $job->source_item) }}</td></tr>
                    <tr><th>Triggered By</th><td>
                        @if($job->triggeredByInspection)
                            <a href="{{ route('admin.reports.inspection.show', $job->triggered_by_inspection_log_id) }}">
                                Inspection Log #{{ $job->triggered_by_inspection_log_id }}
                            </a>
                        @else
                            Manual / Admin
                        @endif
                    </td></tr>
                </table>
                <div class="mt-3">
                    <label class="fw-bold small">Description / Remark:</label>
                    <p class="border p-2 bg-light rounded small">{{ $job->description }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row">
            <!-- Photos -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">Before Photo</div>
                    <div class="card-body p-2 text-center">
                        @if($job->before_photo)
                            <img src="{{ asset('storage/' . $job->before_photo) }}" class="img-fluid rounded border shadow-sm" style="max-height: 300px;">
                        @else
                            <div class="py-5 text-muted small">No before photo available</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">After Photo</div>
                    <div class="card-body p-2 text-center">
                        @if($job->after_photo)
                            <img src="{{ asset('storage/' . $job->after_photo) }}" class="img-fluid rounded border shadow-sm" style="max-height: 300px;">
                        @else
                            <div class="py-5 text-muted small">No after photo available (In Progress)</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Closing Details -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Maintenance Work Summary</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="fw-bold small">Work Description:</label>
                        <p class="border p-3 bg-light rounded">{{ $job->work_description ?? 'Pending completion...' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-primary">Sparepart Used:</label>
                        <p class="fw-bold">{{ $job->sparepart ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold small text-primary">Quantity:</label>
                        <p class="fw-bold">{{ $job->qty ?? 0 }} pcs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
