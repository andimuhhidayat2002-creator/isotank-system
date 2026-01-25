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
            <table id="inspectionLogTable" class="table table-hover align-middle">
                <thead class="table-light">
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
                        <td>{{ $log->inspection_date }}</td>
                        <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $log->isotank_id) }}" class="text-decoration-none text-primary">{{ $log->isotank->iso_number ?? '-' }}</a></td>
                        <td class="text-dark">{{ str_replace('_', ' ', strtoupper($log->inspection_type)) }}</td>
                        <td class="text-muted">{{ $log->inspector->name ?? '-' }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">LOGGED</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($log->pdf_path)
                                    <a href="{{ asset('storage/' . $log->pdf_path) }}" target="_blank" class="btn btn-outline-danger" title="View PDF">
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
                <tfoot class="table-light">
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
$(document).ready(function() {
    $('#inspectionLogTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
        }
    });

    var table = $('#inspectionLogTable').DataTable({
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
        order: [[0, 'desc']],
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
