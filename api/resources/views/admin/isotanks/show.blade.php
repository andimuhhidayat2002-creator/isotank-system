@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Isotank Details</h2>
        <span class="text-muted">{{ $isotank->iso_number }}</span>
    </div>
    <a href="{{ route('admin.isotanks.index') }}" class="btn btn-secondary">Back to List</a>
</div>

<div class="row">
    <!-- LEFT: Overview Card -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">Overview</div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>ISO Number</th><td class="fw-bold">{{ $isotank->iso_number }}</td></tr>
                    <tr><th>Owner</th><td>{{ $isotank->owner ?? '-' }}</td></tr>
                    <tr><th>Location</th><td>{{ $isotank->location ?? '-' }}</td></tr>
                    <tr><th>Product</th><td>{{ $isotank->product ?? '-' }}</td></tr>
                    <tr><th>Filling Status</th>
                        <td>
                            <span class="badge {{ $isotank->filling_status_code=='filled'?'bg-success':'bg-secondary' }}">
                                {{ $isotank->filling_status_desc ?? $isotank->filling_status_code ?? 'Empty' }}
                            </span>
                        </td>
                    </tr>
                    <tr><th>Status</th><td>{{ ucfirst($isotank->status) }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">Technical Specs</div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Manufacturer</th><td>{{ $isotank->manufacturer ?? '-' }}</td></tr>
                    <tr><th>Serial No</th><td>{{ $isotank->manufacturer_serial_number ?? '-' }}</td></tr>
                    <tr><th>Model Type</th><td>{{ $isotank->model_type ?? '-' }}</td></tr>
                    <tr><th>Capacity</th><td>{{ $isotank->capacity ? $isotank->capacity.' L' : '-' }}</td></tr>
                    <tr><th>Tare Weight</th><td>{{ $isotank->tare_weight ? $isotank->tare_weight.' Kg' : '-' }}</td></tr>
                    <tr><th>Max Gross</th><td>{{ $isotank->max_gross_weight ? $isotank->max_gross_weight.' Kg' : '-' }}</td></tr>
                </table>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4">
             <div class="card-header bg-warning text-dark">Certificates & Dates</div>
             <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Init Pressure Test</th><td>{{ $isotank->initial_pressure_test_date ? $isotank->initial_pressure_test_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><th>CSC Init Test</th><td>{{ $isotank->csc_initial_test_date ? $isotank->csc_initial_test_date->format('d/m/Y') : '-' }}</td></tr>
                     <tr><td colspan="2"><hr class="my-1"></td></tr>
                    <tr><th>Class Expiry</th><td class="fw-bold {{ $isotank->class_survey_expiry_date && $isotank->class_survey_expiry_date->isPast() ? 'text-danger' : '' }}">{{ $isotank->class_survey_expiry_date ? $isotank->class_survey_expiry_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><th>CSC Expiry</th><td class="fw-bold {{ $isotank->csc_survey_expiry_date && $isotank->csc_survey_expiry_date->isPast() ? 'text-danger' : '' }}">{{ $isotank->csc_survey_expiry_date ? $isotank->csc_survey_expiry_date->format('d/m/Y') : '-' }}</td></tr>
                </table>
             </div>
        </div>
    </div>

    <!-- RIGHT: Tabs for History -->
    <div class="col-md-8">
        <ul class="nav nav-tabs mb-3 shadow-sm p-2 bg-white rounded" id="historyTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#condition">Latest Condition</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inspections">Inspection History</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#maintenance">Maintenance History</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#calib">Calibration</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vacuum">Vacuum Logs</button></li>
        </ul>

        <div class="tab-content">
            <!-- Latest Condition -->
            <div class="tab-pane fade show active" id="condition">
                 @if($isotank->latestInspection)
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5>Last Inspection: {{ $isotank->latestInspection->updated_at->format('d M Y') }}</h5>
                            <p>Inspector: {{ $isotank->latestInspection->inspector->name ?? '-' }}</p>
                            @php $log = $isotank->latestInspection; @endphp
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between"><span>Vacuum</span> <strong>{{ $log->vacuum_value ? (float)$log->vacuum_value : '-' }}</strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Pressure</span> <strong>{{ $log->pressure_1 ? (float)$log->pressure_1 : '-' }}</strong></li>
                                        <li class="list-group-item d-flex justify-content-between"><span>Level</span> <strong>{{ $log->level_1 ? (float)$log->level_1 : '-' }}</strong></li>
                                    </ul>
                                </div>
                            <div class="mt-4">
                                <h6>Items Condition</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Condition</th>
                                                <th>Last Checked</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Get all item statuses for this isotank
                                                $statuses = \App\Models\MasterIsotankItemStatus::where('isotank_id', $isotank->id)->get();
                                            @endphp
                                            @forelse($statuses as $status)
                                                <tr>
                                                    <td>{{ ucwords(str_replace('_', ' ', $status->item_name)) }}</td>
                                                    <td>
                                                        @php
                                                            $cls = 'secondary';
                                                            $txt = strtoupper($status->condition);
                                                            if($status->condition == 'good') $cls = 'success';
                                                            elseif($status->condition == 'not_good') $cls = 'danger';
                                                            elseif($status->condition == 'need_attention') $cls = 'warning';
                                                        @endphp
                                                        <span class="badge bg-{{ $cls }}">{{ $txt }}</span>
                                                    </td>
                                                    <td>{{ $status->updated_at->format('Y-m-d') }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">No status data recorded yet.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                                <a href="{{ route('admin.reports.inspection.show', \App\Models\InspectionLog::where('isotank_id', $isotank->id)->latest()->first()->id ?? 0) }}" class="btn btn-primary btn-sm">View Full Last Report</a>
                            </div>
                        </div>
                    </div>
                 @else
                    <div class="alert alert-warning">No detailed inspection data available yet.</div>
                 @endif
            </div>

            <!-- Inspection History -->
            <div class="tab-pane fade" id="inspections">
                <div class="card shadow-sm"><div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Inspector</th><th>Status</th><th>PDF</th></tr></thead>
                    <tbody>
                        @forelse($inspections as $ins)
                        <tr>
                            <td>{{ $ins->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ strtoupper(str_replace('_',' ', $ins->inspection_type)) }}</td>
                            <td>{{ $ins->inspector->name ?? '-' }}</td>
                            <td>{{ $ins->filling_status_desc }}</td>
                            <td>
                                @if($ins->pdf_path) <a href="{{ asset('storage/'.$ins->pdf_path) }}" target="_blank" class="btn btn-xs btn-danger"><i class="bi bi-pdf"></i> PDF</a> @endif
                                <a href="{{ route('admin.reports.inspection.show', $ins->id) }}" class="btn btn-xs btn-info"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No inspections found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div></div>
            </div>

            <!-- Maintenance History -->
            <div class="tab-pane fade" id="maintenance">
                 <div class="card shadow-sm"><div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Job Type</th><th>Status</th><th>Technician</th><th>Desc</th></tr></thead>
                    <tbody>
                        @forelse($maintenance as $job)
                        <tr>
                            <td>{{ $job->created_at->format('Y-m-d') }}</td>
                            <td>{{ $job->job_type }}</td>
                            <td><span class="badge {{ $job->status=='completed'?'bg-success':'bg-warning' }}">{{ strtoupper($job->status) }}</span></td>
                            <td>{{ $job->completedBy->name ?? '-' }}</td>
                            <td>{{ Str::limit($job->description, 30) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No maintenance history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                 </div></div>
            </div>
            
              <div class="tab-pane fade" id="calib">
                  <div class="card shadow-sm"><div class="card-body">
                      <h5>Calibration Status</h5>
                      
                      <!-- General Status Alert -->
                      @if($isotank->calibrationStatuses->where('item_name','General')->first())
                        @php $genStatus = $isotank->calibrationStatuses->where('item_name','General')->first(); @endphp
                        <div class="alert {{ $genStatus->status=='valid' ? 'alert-success' : 'alert-danger' }} mb-3">
                            <strong>Overall Status: {{ strtoupper($genStatus->status) }}</strong> 
                            (Earliest Expiry: {{ $genStatus->valid_until ? $genStatus->valid_until->format('Y-m-d') : '-' }})
                        </div>
                      @endif

                      <div class="table-responsive">
                          <table class="table table-bordered table-striped">
                              <thead class="table-light">
                                  <tr>
                                      <th>Component</th>
                                      <th>Position</th>
                                      <th>Serial No</th>
                                      <th>Cert Number</th>
                                      <th>Set Pressure</th>
                                      <th>Cal Date</th>
                                      <th>Expiry Date</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  @forelse($isotank->components as $comp)
                                    <tr>
                                        <td>{{ $comp->component_type }}</td>
                                        <td>{{ $comp->position_code }}</td>
                                        <td>{{ $comp->serial_number ?? '-' }}</td>
                                        <td>{{ $comp->certificate_number ?? '-' }}</td>
                                        <td>{{ $comp->set_pressure ? $comp->set_pressure . ' MPa' : '-' }}</td>
                                        <td>{{ $comp->last_calibration_date ? $comp->last_calibration_date->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            @if($comp->expiry_date)
                                                <span class="badge {{ $comp->expiry_date->isPast() ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $comp->expiry_date->format('Y-m-d') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                  @empty
                                    <tr><td colspan="7" class="text-center">No components registered.</td></tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div></div>
             </div>
             
             <!-- Vacuum Logs -->
             <div class="tab-pane fade" id="vacuum">
                   <div class="card shadow-sm"><div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Check Date</th><th>Value</th><th>Temp</th><th>Remark</th></tr></thead>
                        <tbody>
                            @forelse($vacuumLogs as $v)
                            <tr>
                                <td>{{ $v->check_datetime ? $v->check_datetime->format('Y-m-d H:i') : '-' }}</td>
                                <td>{{ $v->vacuum_value_raw ?? $v->vacuum_value_mtorr ?? '-' }} {{ $v->vacuum_unit_raw ?? 'mTorr' }}</td>
                                <td>{{ $v->temperature }} &deg;C</td>
                                <td>{{ $v->remarks }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">No vacuum logs recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                   </div></div>
             </div>
        </div>
    </div>
</div>
@endsection
