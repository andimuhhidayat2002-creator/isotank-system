@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Master Isotanks</h2>
        <div class="d-flex gap-2">
            <!-- Search Form -->
            <form action="{{ route('admin.isotanks.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control" placeholder="Search ISO Number..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-outline-secondary ms-1"><i class="bi bi-search"></i></button>
                @if(request('search'))
                    <a href="{{ route('admin.isotanks.index') }}" class="btn btn-outline-danger ms-1" title="Clear Search"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>

            @if(auth()->user()->role === 'admin')
            <button class="btn btn-outline-primary d-none" id="bulkUpdateBtn" data-bs-toggle="modal" data-bs-target="#bulkSurveyModal">
                <i class="bi bi-calendar-check"></i> Bulk Survey Update <span class="badge bg-primary text-white ms-1" id="selectedCount">0</span>
            </button>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-file-earmark-excel"></i> Upload Excel
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                + New Isotank
            </button>
            @endif
        </div>
    </div>

    <!-- ... (Table Content) ... -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table id="isotankTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="selectAllIsotanks" class="form-check-input"></th>
                        <th>ISO Number</th>
                        <th>Owner</th>
                        <th>Manuf. / Model</th>
                        <th>Serial No</th>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Filling Status</th>
                        <th>Status</th>
                        <th>Init. Pres. Test</th>
                        <th>CSC Initial Test</th>
                        <th>CSC Expiry</th>
                        <th>Class Expiry</th>
                        <th>Calib. Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($isotanks as $iso)
                    <tr>
                        <td><input type="checkbox" class="form-check-input iso-checkbox" value="{{ $iso->id }}"></td>
                        <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $iso->id) }}" class="text-decoration-none">{{ $iso->iso_number }}</a></td>
                        <td>{{ $iso->owner ?? '-' }}</td>
                        <td>
                            {{ $iso->manufacturer ?? '-' }} <br>
                            <small class="text-muted">{{ $iso->model_type ?? '' }}</small>
                        </td>
                        <td>{{ $iso->manufacturer_serial_number ?? '-' }}</td>
                        <td>{{ $iso->location ?? '-' }}</td>
                        <td>{{ $iso->product ?? '-' }}</td>
                        <td>
                            @php
                                $fillingBadge = 'secondary';
                                $fillingText = $iso->filling_status_desc ?? $iso->filling_status_code ?? 'Not Set';
                                if($iso->filling_status_code === 'filled') $fillingBadge = 'success';
                                elseif(in_array($iso->filling_status_code, ['ready_to_fill', 'ongoing_inspection'])) $fillingBadge = 'info';
                                elseif(in_array($iso->filling_status_code, ['under_maintenance', 'class_survey'])) $fillingBadge = 'warning';
                            @endphp
                            <span class="badge bg-{{ $fillingBadge }}">{{ $fillingText }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $iso->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($iso->status) }}
                            </span>
                        </td>
                        
                        <!-- Dates -->
                        <td>{{ $iso->initial_pressure_test_date ? $iso->initial_pressure_test_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $iso->csc_initial_test_date ? $iso->csc_initial_test_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $iso->csc_survey_expiry_date ? $iso->csc_survey_expiry_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            {{ $iso->class_survey_expiry_date ? $iso->class_survey_expiry_date->format('d/m/Y') : '-' }}
                        </td>

                        <td>
                            @php 
                                $statuses = $iso->calibrationStatuses;
                                $count = $statuses->count();
                                $hasIssues = false;
                                foreach($statuses as $s) {
                                    if($s->status !== 'valid' || ($s->valid_until && $s->valid_until->isPast())) {
                                        $hasIssues = true;
                                    }
                                }
                            @endphp

                            @if($count === 0)
                                <span class="badge bg-secondary">No Data</span>
                            @else
                                <div class="dropdown">
                                    <button class="btn btn-sm {{ $hasIssues ? 'btn-danger' : 'btn-success' }} dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem;">
                                        {{ $hasIssues ? 'ATTENTION' : 'VALID' }} ({{ $count }})
                                    </button>
                                    <ul class="dropdown-menu shadow p-2" style="min-width: 250px;">
                                        @foreach($statuses as $cal)
                                            <li class="mb-1 border-bottom pb-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="fw-bold">{{ str_replace('_', ' ', $cal->item_name) }}</small>
                                                    @if($cal->status === 'valid')
                                                        <span class="badge bg-success" style="font-size: 0.6rem;">VALID</span>
                                                    @else
                                                        <span class="badge bg-danger" style="font-size: 0.6rem;">{{ strtoupper($cal->status) }}</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block" style="font-size: 0.65rem;">
                                                    Exp: {{ $cal->valid_until ? $cal->valid_until->format('Y-m-d') : 'N/A' }}
                                                </small>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </td>
                        <!-- Status Moved Up -->
                        <td class="text-nowrap">
                            @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.isotanks.show', $iso->id) }}" class="btn btn-sm btn-outline-primary me-1" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="{{ route('admin.isotanks.toggle', $iso->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                    {{ $iso->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th></th>
                        <th>ISO Number</th>
                        <th>Owner</th>
                        <th>Manuf/Model</th>
                        <th>Serial No</th>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Filling Status</th>
                        <th>Status</th>
                        <th>Init. Pres. Test</th>
                        <th>CSC Initial Test</th>
                        <th>CSC Expiry</th>
                        <th>Class Expiry</th>
                        <th>Calib. Status</th>
                        <th>-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.isotanks.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Isotank (Manual)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs mb-3" id="isoTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">General Info</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="cert-tab" data-bs-toggle="tab" data-bs-target="#cert" type="button">Certificates & Dates</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="isoTabContent">
                            <!-- GENERAL TAB -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">ISO Number <span class="text-danger">*</span></label>
                                    <input type="text" name="iso_number" class="form-control" required placeholder="Ex: HAIU123456-7">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Owner</label>
                                        <input type="text" name="owner" class="form-control" placeholder="Company Name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control" placeholder="Yard Location">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Manufacturer</label>
                                        <input type="text" name="manufacturer" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Manufacturer Serial No</label>
                                        <input type="text" name="manufacturer_serial_number" class="form-control">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Model Type</label>
                                    <input type="text" name="model_type" class="form-control" placeholder="e.g. T11, T14">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Product</label>
                                    <input type="text" name="product" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Initial Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- CERTIFICATES TAB -->
                            <div class="tab-pane fade" id="cert" role="tabpanel">
                                <h6 class="text-muted mb-3">Initial Test Dates</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Initial Pressure Test</label>
                                        <input type="date" name="initial_pressure_test_date" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">CSC Initial Test</label>
                                        <input type="date" name="csc_initial_test_date" class="form-control">
                                    </div>
                                </div>
                                <hr>
                                <h6 class="text-muted mb-3">Expiry Dates (Surveys)</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Class Survey Expiry</label>
                                        <input type="date" name="class_survey_expiry_date" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">CSC Survey Expiry</label>
                                        <input type="date" name="csc_survey_expiry_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Isotank</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.isotanks.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Upload Isotanks</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <small>
                                <strong>Required Columns:</strong> iso_number<br>
                                <strong>Optional:</strong> product, owner, manufacturer, model_type, location, status, dates...<br>
                                <em>Existing ISO Numbers will be UPDATED. New ones CREATED.</em>
                            </small>
                            <div class="mt-2 text-end">
                                <a href="{{ route('admin.isotanks.template') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i> Download Template (CSV)
                                </a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Upload & Process</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Survey Modal -->
    <div class="modal fade" id="surveyModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="surveyForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Class Survey</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Adding new survey record for Isotank: <strong id="surveyIsoNumber"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">Classification Date</label>
                            <input type="date" name="survey_date" class="form-control" required>
                            <div class="form-text">When the survey was performed.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Next Survey Due Date</label>
                            <input type="date" name="next_survey_date" class="form-control" required>
                            <div class="form-text">Isotank must be re-surveyed by this date.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Survey</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Survey Modal -->
    <div class="modal fade" id="bulkSurveyModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="bulkSurveyForm" action="{{ route('admin.isotanks.survey.bulk') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Update Class Survey</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Updating survey record for <strong id="bulkCountDisplay">0</strong> selected isotanks.</p>
                        <!-- Hidden inputs will be appended here by JS -->
                        <div id="bulkHiddenInputs"></div>
                        
                        <div class="mb-3">
                            <label class="form-label">Classification Date</label>
                            <input type="date" name="survey_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Next Survey Due Date</label>
                            <input type="date" name="next_survey_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Bulk Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    // Setup - add a text input to each footer cell
    $('#isotankTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
        }
    });

    var table = $('#isotankTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm mb-3',
                exportOptions: { columns: [1,2,3,4,5,6,7,8,9,11] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm mb-3',
                exportOptions: { columns: [1,2,3,4,5,6,7,8,9,11] }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-info btn-sm mb-3',
                exportOptions: { columns: [1,2,3,4,5,6,7,8,9,11] }
            }
        ],
        pageLength: 25,
        order: [[0, 'asc']],
        initComplete: function() {
            // Apply the search
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

    // Handle Survey Modal
    $(document).on('click', '.survey-btn', function() {
        var id = $(this).data('id');
        var iso = $(this).data('isonumber');
        var url = "{{ route('admin.isotanks.survey.store', ':id') }}";
        url = url.replace(':id', id);
        
        $('#surveyForm').attr('action', url);
        $('#surveyIsoNumber').text(iso);
    });

    // Bulk Select Logic
    $('#selectAllIsotanks').change(function() {
        $('.iso-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkUI();
    });

    $(document).on('change', '.iso-checkbox', function() {
        updateBulkUI();
    });

    function updateBulkUI() {
        var count = $('.iso-checkbox:checked').length;
        $('#selectedCount').text(count);
        if(count > 0) {
            $('#bulkUpdateBtn').removeClass('d-none');
        } else {
            $('#bulkUpdateBtn').addClass('d-none');
        }
    }

    // Populate Bulk Modal
    $('#bulkSurveyModal').on('show.bs.modal', function() {
        var container = $('#bulkHiddenInputs');
        container.empty();
        var count = 0;
        $('.iso-checkbox:checked').each(function() {
            container.append('<input type="hidden" name="isotank_ids[]" value="'+$(this).val()+'">');
            count++;
        });
        $('#bulkCountDisplay').text(count);
    });
});
</script>
@endpush
@endsection
