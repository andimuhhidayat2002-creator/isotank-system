@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Maintenance History</h2>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <table id="maintenanceTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ISO Number</th>
                        <th>Item</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td class="fw-bold">{{ $job->isotank->iso_number ?? '-' }}</td>
                        <td class="text-uppercase small fw-bold text-muted">{{ str_replace('_', ' ', $job->source_item) }}</td>
                        <td>
                            <span class="badge bg-{{ $job->priority === 'urgent' || $job->priority === 'high' ? 'danger' : ($job->priority === 'normal' ? 'primary' : 'info') }}" style="font-size: 0.7rem;">
                                {{ strtoupper($job->priority) }}
                            </span>
                        </td>
                        <td>
                            @if($job->status === 'closed')
                                <span class="badge bg-success">CLOSED</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ strtoupper(str_replace('_', ' ', $job->status)) }}</span>
                            @endif
                        </td>
                        <td>{{ $job->assignee->name ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.reports.maintenance.show', $job->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>ISO Number</th>
                        <th>Item</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#maintenanceTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
        }
    });

    $('#maintenanceTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm mb-3',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm mb-3',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
        initComplete: function() {
            this.api().columns().every(function() {
                var that = this;
                $('input', this.footer()).on('keyup change clear', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });
            });
        }
    });
});
</script>
@endpush
@endsection
