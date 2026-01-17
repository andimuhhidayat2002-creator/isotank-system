@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Calibration Reports & History</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table id="calibrationTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ISO Number</th>
                        <th>Item Name</th>
                        <th>Status</th>
                        <th>Calib. Date</th>
                        <th>Valid Until</th>
                        <th>Serial Number</th>
                        <th>Vendor</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td class="fw-bold">{{ $log->isotank->iso_number ?? '-' }}</td>
                        <td class="text-uppercase small">{{ str_replace('_', ' ', $log->item_name) }}</td>
                        <td>
                            @if($log->status === 'completed')
                                <span class="badge bg-success">COMPLETED</span>
                            @elseif($log->status === 'rejected')
                                <span class="badge bg-danger">REJECTED</span>
                            @elseif($log->status === 'planned')
                                <span class="badge bg-info">PLANNED</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ strtoupper($log->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $log->calibration_date ? $log->calibration_date->format('Y-m-d') : '-' }}</td>
                        <td>
                            @if($log->valid_until)
                                <span class="{{ $log->valid_until->lt(now()) ? 'text-danger fw-bold' : '' }}">
                                    {{ $log->valid_until->format('Y-m-d') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($log->status === 'rejected' && $log->replacement_serial)
                                <span class="text-decoration-line-through text-danger">{{ $log->serial_number }}</span>
                                <span class="text-muted mx-1">➜</span>
                                <span class="text-success fw-bold">{{ $log->replacement_serial }}</span>
                            @else
                                {{ $log->serial_number ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $log->vendor ?? '-' }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#notesModal{{ $log->id }}">
                                View
                            </button>
                        </td>
                    </tr>

                    <!-- Notes Modal -->
                    <div class="modal fade" id="notesModal{{ $log->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Details for {{ $log->isotank->iso_number ?? 'Job' }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <strong>Description:</strong>
                                    <p>{{ $log->description ?? 'N/A' }}</p>
                                    <hr>
                                    <strong>Inspector/Creator:</strong>
                                    <p>{{ $log->creator->name ?? '-' }}</p>
                                    <hr>
                                    <strong>Notes:</strong>
                                    <p class="mb-0">{{ $log->notes ?? 'No additional notes provided.' }}</p>
                                    @if($log->replacement_serial)
                                        <div class="mt-3 alert alert-warning">
                                            <strong>Replacement Info:</strong><br>
                                            Serial: {{ $log->replacement_serial }}<br>
                                            Calib. Date: {{ $log->replacement_calibration_date ? $log->replacement_calibration_date->format('Y-m-d') : '-' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>ISO Number</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Calib. Date</th>
                        <th>Valid Until</th>
                        <th>Serial</th>
                        <th>Vendor</th>
                        <th>-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#calibrationTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
        }
    });

    $('#calibrationTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm mb-3',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm mb-3',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
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
