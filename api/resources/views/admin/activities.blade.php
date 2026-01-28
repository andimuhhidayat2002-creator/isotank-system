@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Activity Planner (Upload)</h2>
        <!-- Search Form -->
        <form action="{{ route('admin.activities.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control" placeholder="Search Activity by Last 4 Digits..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary ms-1"><i class="bi bi-search"></i></button>
            @if(request('search'))
                <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-danger ms-1" title="Clear Search"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <div class="row mb-5">
        <!-- Inspection -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Inspection Activity</span>
                    <i class="bi bi-search"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Plan an Incoming or Outgoing inspection for an Isotank.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualInspectionModal">
                            <i class="bi bi-plus-circle"></i> Add Manually
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadInspectionModal">
                            <i class="bi bi-file-earmark-excel"></i> Upload Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span>Maintenance Activity</span>
                    <i class="bi bi-tools"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Create a maintenance job for a specific item (e.g. Shell, Valves).</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#manualMaintenanceModal">
                            <i class="bi bi-plus-circle"></i> Add Manually
                        </button>
                        <button class="btn btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#uploadMaintenanceModal">
                            <i class="bi bi-file-earmark-excel"></i> Upload Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calibration -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Calibration Activity</span>
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Schedule calibration for gauges, relief valves, or thermometers.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#manualCalibrationModal">
                            <i class="bi bi-plus-circle"></i> Add Manually
                        </button>
                        <button class="btn btn-outline-info text-dark" data-bs-toggle="modal" data-bs-target="#uploadCalibrationModal">
                            <i class="bi bi-file-earmark-excel"></i> Upload Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vacuum -->
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Vacuum Activity</span>
                    <i class="bi bi-speedometer"></i>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Upload historical vacuum readings or schedule checks.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger text-dark" data-bs-toggle="modal" data-bs-target="#uploadVacuumModal">
                            <i class="bi bi-file-earmark-excel"></i> Upload Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS SECTION -->

    <!-- Manual Inspection Modal -->
    <div class="modal fade" id="manualInspectionModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.manual') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Plan Inspection (Manual)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ISO Number</label>
                            <input type="text" name="iso_number" class="form-control" required placeholder="ISOXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Activity Type</label>
                            <select name="activity_type" class="form-select" required>
                                <option value="incoming_inspection">Incoming Inspection</option>
                                <option value="outgoing_inspection">Outgoing Inspection</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Planned Date</label>
                            <input type="date" name="planned_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3 destination-field" style="display:none;">
                            <label class="form-label">Destination (Outgoing Only)</label>
                            <input type="text" name="destination" class="form-control" placeholder="Client / Location">
                        </div>
                        <!-- Receiver Name Removed -->
                        <div class="mb-3 filling-field" style="display:none;">
                            <label class="form-label">Filling Status</label>
                            <select name="filling_status_code" class="form-select">
                                <option value="">-- Select Status --</option>
                                <option value="ongoing_inspection" data-desc="Ongoing Inspection">Ongoing Inspection</option>
                                <option value="ready_to_fill" data-desc="Ready to Fill">Ready to Fill</option>
                                <option value="filled" data-desc="Filled">Filled</option>
                                <option value="under_maintenance" data-desc="Under Maintenance">Under Maintenance</option>
                                <option value="waiting_team_calibration" data-desc="Waiting Team Calibration">Waiting Team Calibration</option>
                                <option value="class_survey" data-desc="Class Survey">Class Survey</option>
                            </select>
                        </div>
                        <div class="mb-3 filling-field" style="display:none;">
                            <label class="form-label">Filling Status Description</label>
                            <input type="text" name="filling_status_desc" class="form-control" placeholder="Description of contents or status">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Plan Activity</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Manual Maintenance Modal -->
    <div class="modal fade" id="manualMaintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.manual') }}" method="POST">
                @csrf
                <input type="hidden" name="activity_type" value="maintenance">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Maintenance Job (Manual)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ISO Number</label>
                            <input type="text" name="iso_number" class="form-control" required placeholder="ISOXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" required placeholder="e.g. Shell, Bottom Valve">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" required rows="3"></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Part Damage <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="part_damage" class="form-control" placeholder="e.g. Crack, Dent">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Damage Type <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="damage_type" class="form-control" placeholder="e.g. Major, Minor">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Location <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. Left Side">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Planned Date</label>
                                <input type="date" name="planned_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Create Job</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Manual Calibration Modal -->
    <div class="modal fade" id="manualCalibrationModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.manual') }}" method="POST">
                @csrf
                <input type="hidden" name="activity_type" value="calibration">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Schedule Calibration (Manual)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ISO Number</label>
                            <input type="text" name="iso_number" class="form-control" required placeholder="ISOXXXXXX">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Items to Calibrate</label>
                            <div class="border p-2 rounded">
                                <div class="form-check">
                                    <input class="form-check-input calib-item-check" type="checkbox" name="item_names[]" value="Pressure Gauge" id="item_pg">
                                    <label class="form-check-label" for="item_pg">Pressure Gauge</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input calib-item-check" type="checkbox" name="item_names[]" value="PSV 1" id="item_psv1">
                                    <label class="form-check-label" for="item_psv1">PSV 1</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input calib-item-check" type="checkbox" name="item_names[]" value="PSV 2" id="item_psv2">
                                    <label class="form-check-label" for="item_psv2">PSV 2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input calib-item-check" type="checkbox" name="item_names[]" value="PSV 3" id="item_psv3">
                                    <label class="form-check-label" for="item_psv3">PSV 3</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input calib-item-check" type="checkbox" name="item_names[]" value="PSV 4" id="item_psv4">
                                    <label class="form-check-label" for="item_psv4">PSV 4</label>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Serial Numbers Container -->
                        <div id="calibrationSerialContainer" class="mb-3" style="display:none;">
                            <label class="form-label">Serial Numbers</label>
                            <div id="calibrationSerialInputs">
                                <!-- Dynamic inputs appended here -->
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" required rows="2">Annual Calibration</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Planned Date</label>
                                <input type="date" name="planned_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="vendor" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                           const container = document.getElementById('calibrationSerialContainer');
                           const inputsDiv = document.getElementById('calibrationSerialInputs');
                           const checkboxes = document.querySelectorAll('.calib-item-check');

                           checkboxes.forEach(cb => {
                               cb.addEventListener('change', updateSerialInputs);
                           });

                           function updateSerialInputs() {
                               inputsDiv.innerHTML = '';
                               let anyChecked = false;

                               checkboxes.forEach(cb => {
                                   if(cb.checked) {
                                       anyChecked = true;
                                       const itemName = cb.value;
                                       // Create input group
                                       const div = document.createElement('div');
                                       div.className = 'mb-2 input-group';
                                       
                                       const span = document.createElement('span');
                                       span.className = 'input-group-text';
                                       span.style.width = '120px';
                                       span.textContent = itemName;

                                       const input = document.createElement('input');
                                       input.type = 'text';
                                       input.className = 'form-control';
                                       input.name = 'serial_numbers[' + itemName + ']'; 
                                       input.required = true;
                                       input.placeholder = 'Enter SN for ' + itemName;

                                       div.appendChild(span);
                                       div.appendChild(input);
                                       inputsDiv.appendChild(div);
                                   }
                               });

                               container.style.display = anyChecked ? 'block' : 'none';
                           }
                        });
                    </script>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info text-white">Schedule</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modals (Hidden by default, triggered by buttons) -->
    
    <!-- Upload Inspection Modal -->
    <div class="modal fade" id="uploadInspectionModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Inspection Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Please use the template below. Rows with empty ISO Numbers will be ignored.
                            <br>
                            <a href="{{ route('admin.templates.inspection') }}" class="btn btn-sm btn-outline-info mt-2">Download Template</a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="activity_type" class="form-select">
                                <option value="incoming_inspection">Incoming Inspection</option>
                                <option value="outgoing_inspection">Outgoing Inspection</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Process Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Maintenance Modal -->
    <div class="modal fade" id="uploadMaintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="activity_type" value="maintenance">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Maintenance Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Please use the template below. Rows with empty ISO Numbers will be ignored.
                            <br>
                            <strong>New!</strong> You can now add optional columns: <em>Part Damage</em> (Col F), <em>Damage Type</em> (Col G), <em>Location</em> (Col H).
                            <br>
                            <a href="{{ route('admin.templates.maintenance') }}" class="btn btn-sm btn-outline-info mt-2">Download Template</a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Process Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Calibration Modal -->
    <div class="modal fade" id="uploadCalibrationModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="activity_type" value="calibration">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Calibration Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Please use the template below. Rows with empty ISO Numbers will be ignored.
                            <br>
                            <a href="{{ route('admin.templates.calibration') }}" class="btn btn-sm btn-outline-info mt-2">Download Template</a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-info text-white">Process Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Vacuum Modal -->
    <div class="modal fade" id="uploadVacuumModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.activities.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="activity_type" value="vacuum">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Vacuum Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Please use the template below. Rows with empty ISO Numbers will be ignored.
                            <br>
                            <a href="{{ route('admin.templates.vacuum') }}" class="btn btn-sm btn-outline-info mt-2">Download Template</a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Excel File (.xlsx, .csv)</label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger text-white">Process Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('select[name="activity_type"]').forEach(select => {
            select.addEventListener('change', function() {
                // Find the closest modal body or form
                const container = this.closest('.modal-body') || this.closest('form');
                const dest = container.querySelector('#destinationField') || container.querySelector('.destination-field');
                
                if (dest) {
                    const receiver = container.querySelector('.receiver-field');
                    const fillingFields = container.querySelectorAll('.filling-field');
                    
                    // Destination & Receiver: ONLY Outgoing
                    if (this.value === 'outgoing_inspection') {
                        dest.style.display = 'block';
                        dest.querySelector('input').setAttribute('required', 'required');
                        
                        // Receiver field logic removed
                    } else {
                        dest.style.display = 'none';
                        dest.querySelector('input').removeAttribute('required');
                        
                        // Receiver logic removed
                    }

                    // Filling Status: BOTH Incoming and Outgoing
                    if (fillingFields && fillingFields.length > 0) {
                        if (this.value === 'incoming_inspection' || this.value === 'outgoing_inspection') {
                             fillingFields.forEach(f => {
                                 f.style.display = 'block';
                                 const input = f.querySelector('input, select');
                                 if (input) input.setAttribute('required', 'required');
                             });
                        } else {
                             fillingFields.forEach(f => {
                                 f.style.display = 'none';
                                 const input = f.querySelector('input, select');
                                 if (input) input.removeAttribute('required');
                             });
                        }
                    }
                }
            });
            
            // Trigger change on load to set initial state
            select.dispatchEvent(new Event('change'));
        });

        // Auto-fill description based on selected status
        const fillingSelect = document.querySelector('select[name="filling_status_code"]');
        if (fillingSelect) {
            fillingSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const desc = selectedOption.getAttribute('data-desc');
                // Find input in the same form
                const form = this.closest('form');
                if (form) {
                    const descInput = form.querySelector('input[name="filling_status_desc"]');
                    if (descInput) {
                        descInput.value = desc || ''; 
                    }
                }
            });
        }
    </script>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Pending / Planned Activities</h4>
        <span class="badge bg-secondary">Monitoring queue for inspectors & mechanics</span>
    </div>

    <div class="row mb-5">
        <!-- Pending Inspections -->
        <div class="col-md-12 mb-4">
            <div class="card border-0">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-search me-2"></i>Pending Inspections</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">{{ count($pendingInspections) }} Open</span>
                </div>
                <div class="card-body">
                    <table id="pendingInspectionsTable" class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ISO Number</th>
                                <th>Type</th>
                                <th>Planned Date</th>
                                <th>Destination</th>
                                <th>Receiver</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($pendingInspections as $job)
                            <tr>
                                <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $job->isotank_id) }}" class="text-decoration-none text-primary">{{ optional($job->isotank)->iso_number ?? 'UNKNOWN' }}</a></td>
                                <td>{{ strtoupper(str_replace('_', ' ', $job->activity_type)) }}</td>
                                <td>{{ $job->planned_date ? $job->planned_date->format('Y-m-d') : '-' }}</td>
                                <td>{{ $job->destination ?? '-' }}</td>
                                <td>{{ $job->receiver_name ?? '-' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.activities.inspection.delete', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this job?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2"><i class="bi bi-trash"></i> Cancel</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>ISO Number</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Dest</th>
                                <th>Rcvr</th>
                                <th>-</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pending Maintenance & Calibration -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 h-100">
                <div class="card-header border-bottom">
                    <i class="bi bi-tools me-2"></i>Pending Maintenance
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ISO</th>
                                <th>Item</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($pendingMaintenance as $m)
                            <tr>
                                <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $m->isotank_id) }}" class="text-decoration-none text-primary">{{ optional($m->isotank)->iso_number ?? 'UNKNOWN' }}</a></td>
                                <td class="text-truncate" style="max-width: 150px;">{{ $m->source_item }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.activities.maintenance.delete', $m->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if($pendingMaintenance->isEmpty())
                                <tr><td colspan="3" class="text-center py-4 text-muted">No pending maintenance jobs</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 h-100">
                <div class="card-header border-bottom">
                    <i class="bi bi-clock-history me-2"></i>Planned Calibrations
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ISO</th>
                                <th>Item</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($pendingCalibrations as $c)
                            <tr>
                                <td class="fw-bold"><a href="{{ route('admin.isotanks.show', $c->isotank_id) }}" class="text-decoration-none text-primary">{{ optional($c->isotank)->iso_number ?? 'UNKNOWN' }}</a></td>
                                <td class="text-truncate" style="max-width: 150px;">
                                    {{ $c->item_name }}
                                    @if($c->serial_number)
                                        <br><span class="text-muted small">SN: {{ $c->serial_number }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.activities.calibration.delete', $c->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @if($pendingCalibrations->isEmpty())
                                <tr><td colspan="3" class="text-center py-4 text-muted">No planned calibrations</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <h4>Upload History</h4>
    <div class="card mt-3">
        <div class="card-body">
        <table id="uploadHistoryTable" class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Rows</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at ? $log->created_at->format('Y-m-d H:i') : '-' }}</td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">{{ ucfirst(str_replace('_', ' ', $log->activity_type)) }}</span></td>
                    <td>{{ $log->total_rows }}</td>
                    <td class="text-success fw-bold">{{ $log->success_count }}</td>
                    <td class="{{ $log->error_count > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $log->error_count }}</td>
                    <td>
                        @if($log->error_count > 0 && $log->error_details)
                            <button class="btn btn-sm btn-outline-danger view-error-btn" 
                                    type="button" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#errorDetailModal"
                                    data-details="{{ json_encode($log->error_details) }}">
                                View Errors
                            </button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Rows</th>
                    <th>S</th>
                    <th>F</th>
                    <th>-</th>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
    <!-- Error Detail Modal -->
    <div class="modal fade" id="errorDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Error Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="errorDetailContent" class="bg-light p-3 border rounded" style="max-height: 400px; overflow-y: auto;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Error Modal Handler
    $('.view-error-btn').click(function() {
        var details = $(this).data('details');
        // Format JSON nicely
        var content = JSON.stringify(details, null, 2);
        $('#errorDetailContent').text(content);
    });

    // Pending Inspections Table
    $('#pendingInspectionsTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter" />');
        }
    });

    $('#pendingInspectionsTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['excelHtml5', 'print'],
        pageLength: 10,
        order: [[2, 'asc']],
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

    // Upload History Table
    $('#uploadHistoryTable tfoot th').each(function() {
        var title = $(this).text();
        if (title !== '-') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter" />');
        }
    });

    $('#uploadHistoryTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['excelHtml5'],
        pageLength: 10,
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
