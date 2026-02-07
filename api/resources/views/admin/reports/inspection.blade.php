@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inspection Logs</h2>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
          <a class="nav-link {{ $category == 'all' ? 'active' : '' }}" href="{{ route('admin.reports.inspection', ['category' => 'all']) }}">All</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T75' ? 'active' : '' }}" href="{{ route('admin.reports.inspection', ['category' => 'T75']) }}">T75</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T11' ? 'active' : '' }}" href="{{ route('admin.reports.inspection', ['category' => 'T11']) }}">T11</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T50' ? 'active' : '' }}" href="{{ route('admin.reports.inspection', ['category' => 'T50']) }}">T50</a>
        </li>
    </ul>

    <div class="card mt-4">
        <div class="card-body">
            <table id="inspectionLogTable" class="table table-hover align-middle" data-order='[[ 0, "desc" ]]'>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>ISO Number</th>
                        <th>Type</th>
                        <th>Inspector</th>
                        <th>Result</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @foreach($logs as $log)
                    <tr>
                        <td class="text-white">{{ $log->updated_at->format('Y-m-d H:i') }}</td>
                        <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $log->isotank_id) }}" class="text-decoration-none text-primary">{{ $log->isotank->iso_number ?? 'UNKNOWN' }}</a></td>
                        <td class="text-white">{{ str_replace('_', ' ', strtoupper($log->inspection_type)) }}</td>
                        <td class="text-white">{{ $log->inspector->name ?? '-' }}</td>
                        <td>
                            @if($log->status == 'missing')
                                <span class="badge bg-danger text-white">MISSING</span>
                            @elseif($log->is_draft)
                                <span class="badge bg-secondary text-white">DRAFT</span>
                            @elseif($log->inspection_type == 'outgoing_inspection' && !$log->receiver_confirmed_at)
                                <span class="badge bg-warning text-dark">PENDING CONFIRMATION</span>
                            @elseif($log->inspection_type == 'outgoing_inspection' && $log->receiver_confirmed_at)
                                <span class="badge bg-success text-white">CONFIRMED</span>
                            @else
                                <span class="badge bg-primary text-white">COMPLETED</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($log->pdf_path)
                                    <a href="{{ route('admin.reports.inspection.pdf', $log->id) }}" target="_blank" class="btn btn-outline-danger" title="View PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.reports.inspection.pdf', $log->id) }}" class="btn btn-outline-danger" title="Generate PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                @endif
                                <a href="{{ route('admin.reports.inspection.show', $log->id) }}" class="btn btn-outline-secondary">Details</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Date</th>
                        <th>ISO Number</th>
                        <th>Type</th>
                        <th>Inspector</th>
                        <th>Result</th>
                        <th>-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var waitForDT = setInterval(function() {
        if (window.$ && $.fn.DataTable) {
            clearInterval(waitForDT);
            initInspectionLogTable();
        }
    }, 100);

    function initInspectionLogTable() {
        var $ = window.jQuery;
        $('#inspectionLogTable tfoot th').each(function() {
            var title = $(this).text();
            if (title !== '-') {
                $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
            }
        });

        if ($.fn.DataTable.isDataTable('#inspectionLogTable')) {
            $('#inspectionLogTable').DataTable().clear().destroy();
        }

        var table = $('#inspectionLogTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm mb-3 me-1',
                    exportOptions: { columns: [0, 1, 2, 3, 4] }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="bi bi-file-earmark-pdf"></i> Export PDF',
                    className: 'btn btn-danger btn-sm mb-3 me-1',
                    exportOptions: { columns: [0, 1, 2, 3, 4] }
                }
            ],
            pageLength: 25,
            order: [[0, 'desc']],
            stateSave: false,
            destroy: true,
            ordering: true,
            columnDefs: [
                { orderable: true, targets: 0 },  // Only Date column is sortable
                { orderable: false, targets: '_all' }  // Disable sorting on all other columns
            ],
            initComplete: function() {
                var api = this.api();
                // Force sort
                setTimeout(function() {
                     api.order([0, 'desc']).draw();
                }, 50);

                api.columns().every(function() {
                    var that = this;
                    $('input', this.footer()).on('keyup change clear', function() {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                });
            }
        });
    }
});
</script>
@endpush
@endsection
