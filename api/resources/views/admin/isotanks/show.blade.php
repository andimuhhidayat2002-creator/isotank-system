@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Isotank Details</h2>
        <span class="text-muted">{{ $isotank->iso_number }}</span>
    </div>
    <a href="{{ route('admin.isotanks.index') }}" class="btn btn-secondary">Back to List</a>
</div>

@php
    $activeMaintenance = $maintenance->whereNotIn('status', ['completed', 'closed', 'deferred']);
@endphp

@if($activeMaintenance->isNotEmpty())
    <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm border-danger" role="alert">
        <div class="flex-shrink-0 me-3">
            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2rem;"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading fw-bold mb-1">⚠️ MAINTENANCE REQUIRED</h5>
            <p class="mb-2">This isotank has active maintenance orders that require attention:</p>
            <ul class="mb-0 list-group list-group-flush bg-transparent">
                @foreach($activeMaintenance as $job)
                    <li class="list-group-item bg-transparent py-1 px-0 border-0 text-danger">
                        <i class="bi bi-gear-fill me-2"></i>
                        <strong>{{ $job->source_item ?? 'General' }}:</strong> 
                        {{ Str::limit($job->description, 80) }} 
                        <span class="badge bg-danger ms-1">{{ strtoupper(str_replace('_', ' ', $job->status)) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

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
                                                <th>Category / Item Name</th>
                                                <th class="text-center">Condition/Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $inspectionItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order')->get();
                                                $logData = is_array($log->inspection_data) ? $log->inspection_data : json_decode($log->inspection_data, true) ?? [];
                                                $tankCat = $isotank->tank_category ?? 'T75'; // Default to T75
                                                
                                                // DEBUG: VIEW ACTUAL KEYS
                                                echo "<div class='alert alert-info py-1' style='font-size:10px; max-height:100px; overflow:auto;'><strong>DEBUG DATA KEYS:</strong> " . json_encode(array_keys($logData)) . "</div>";
                                                echo "<div class='alert alert-info py-1' style='font-size:10px;'><strong>DEBUG INFO:</strong> TYPE=" . $log->inspection_type . " | CATEGORY=" . $tankCat . " | ID=" . $log->id . "</div>";
                                                
                                                // DEBUG: Check if data exists in direct columns
                                                $sampleColumns = ['surface', 'frame', 'valve_condition', 'gps_antenna', 'pressure_regulator_esdv'];
                                                $colData = [];
                                                foreach($sampleColumns as $col) {
                                                    if(isset($log->$col)) $colData[$col] = $log->$col;
                                                }
                                                echo "<div class='alert alert-warning py-1' style='font-size:10px;'><strong>DEBUG COLUMNS:</strong> " . json_encode($colData) . "</div>";

                                                // Legacy Map for Fallback (Synchronized with Report View)
                                                // Simplified to match inspection_show.blade.php
                                                $legacyMap = [
                                                    'Surface Condition' => 'surface', 'Tank Surface & Paint Condition' => 'surface',
                                                    'Frame Condition' => 'frame', 'Frame Structure' => 'frame',
                                                    'Tank Name Plate' => 'tank_plate', 'Data Plate' => 'tank_plate',
                                                    'Venting Pipe' => 'venting_pipe',
                                                    'Explosion Proof Cover' => 'explosion_proof_cover',
                                                    'Safety Label' => 'safety_label', 'DG 1972 GHS MSA_Safety_label' => 'safety_label',
                                                    'Document Container' => 'document_container',
                                                    'Valve Box Door' => 'valve_box_door',
                                                    'Grounding System' => 'grounding_system',
                                                    'Valve Condition' => 'valve_condition',
                                                    'Valve Position' => 'valve_position',
                                                    'Pipe Joint' => 'pipe_joint',
                                                    'Air Source Connection' => 'air_source_connection',
                                                    'ESDV' => 'esdv',
                                                    'Blind Flange' => 'blind_flange',
                                                    'PRV' => 'prv'
                                                ];
                                                
                                                // Unmapped Item Logic (ROBUST & NORMALIZED)
                                                $standardCodes = $inspectionItems->pluck('code')->toArray();
                                                $normalizedStandardCodes = array_map(function($c) { 
                                                    return strtolower(str_replace([' ', '-', '.'], '_', $c)); 
                                                }, $standardCodes);

                                                $unmapped = [];
                                                foreach($logData as $k => $v) {
                                                    $normK = strtolower(str_replace([' ', '-', '.'], '_', $k));
                                                    
                                                    if(!in_array($normK, $normalizedStandardCodes) && 
                                                       !in_array($k, $standardCodes) && 
                                                       !in_array($k, ['inspection_date', 'inspector_name', 'filling_status', 'remarks', 'signature', 'longitude', 'latitude', 'location_name']) &&
                                                       is_string($v) && strlen($v) < 50) {
                                                         // Also exclude hardcoded legacy fields if they appear in JSON
                                                         if(!str_contains($normK, 'ibox') && !str_contains($normK, 'vacuum') && !str_contains($normK, 'pressure_gauge') && !str_contains($normK, 'psv')) {
                                                             $unmapped[$k] = $v;
                                                         }
                                                    }
                                                }
                                            @endphp

                                            <!-- DYNAMIC CATEGORIES LOOP -->
                                            @php
                                                // 1. Filter items STRICTLY by Tank Category
                                                $catSpecificItems = $inspectionItems->filter(function($i) use ($tankCat) {
                                                      $cats = $i->applicable_categories;
                                                      if (is_string($cats)) $cats = json_decode($cats, true);
                                                      if (!is_array($cats)) $cats = [];
                                                      return in_array($tankCat, $cats);
                                                });
                                                
                                                // 2. Group by Category
                                                $grouped = $catSpecificItems->groupBy('category');
                                            @endphp

                                            @php
                                                $tCat = $tankCat;
                                                if ($tCat === 'T11') {
                                                    $categoryMap = [
                                                        'a' => 'A. FRONT',
                                                        'b' => 'B. REAR',
                                                        'c' => 'C. RIGHT',
                                                        'd' => 'D. LEFT',
                                                        'e' => 'E. TOP',
                                                        'other' => 'Other / Internal'
                                                    ];
                                                } elseif ($tCat === 'T50') {
                                                    $categoryMap = [
                                                        'a' => 'A. FRONT OUT SIDE VIEW',
                                                        'b' => 'B. REAR OUT SIDE VIEW',
                                                        'c' => 'C. RIGHT SIDE/VALVE BOX OBSERVATION',
                                                        'd' => 'D. LEFT SIDE',
                                                        'e' => 'E. TOP',
                                                        'other' => 'Other / Internal'
                                                    ];
                                                } else {
                                                    $categoryMap = [
                                                        'b' => 'B. GENERAL CONDITION',
                                                        'c' => 'C. VALVES & PIPING',
                                                        'd' => 'D. IBOX SYSTEM',
                                                        'e' => 'E. INSTRUMENTS',
                                                        'f' => 'F. VACUUM SYSTEM',
                                                        'g' => 'G. SAFETY VALVES (PSV)',
                                                    ];
                                                }
                                            @endphp

                                             @foreach($grouped as $categoryName => $items)
                                                @if(($tankCat ?? 'T75') !== 'T75' || !in_array($categoryName, ['d', 'e', 'f', 'g']))
                                                    <tr class="table-secondary"><th colspan="2">{{ $categoryMap[$categoryName] ?? strtoupper($categoryName) }}</th></tr>
                                                    @foreach($items as $item)
                                                        @php 
                                                            $code = $item->code; 
                                                            $label = $item->label;
                                                            $foundAt = null; // DEBUG: Track where value was found
                                                            
                                                            // PRO ROBUST LOOKUP STRATEGY (Synchronized with Inspection Detail View)
                                                            // 1. Direct Code match in JSON
                                                            $val = $logData[$code] ?? null;
                                                            if ($val) $foundAt = "JSON:code";
                                                            
                                                            // 2. Underscore-version of Code in JSON
                                                            if (!$val) {
                                                                $uCode = str_replace([' ', '.', '/'], '_', $code);
                                                                $val = $logData[$uCode] ?? null;
                                                                if ($val) $foundAt = "JSON:uCode";
                                                            }
                                                            
                                                            // 3. Legacy Map (By Label) in JSON
                                                            if (!$val && isset($legacyMap[$label])) {
                                                                $lKey = $legacyMap[$label];
                                                                $val = $logData[$lKey] ?? null;
                                                                if ($val) $foundAt = "JSON:legacyMap";
                                                            }

                                                            // 4. Check for Legacy Label as Key in JSON (e.g. "GPS_4G_LP_LAN_Antenna")
                                                            if (!$val) {
                                                                $uLabel = str_replace([' ', '.', '/'], '_', $label);
                                                                $val = $logData[$uLabel] ?? null;
                                                                if ($val) $foundAt = "JSON:uLabel";
                                                            }
                                                            
                                                            // 5. Try exact label in JSON
                                                            if (!$val) {
                                                                 $val = $logData[$label] ?? null;
                                                                 if ($val) $foundAt = "JSON:label";
                                                            }
                                                            
                                                            // 6. Underscore-version of Lowercase Label in JSON
                                                            if (!$val) {
                                                                $uLabelLower = str_replace([' ', '.', '/'], '_', strtolower($label));
                                                                $val = $logData[$uLabelLower] ?? null;
                                                                if ($val) $foundAt = "JSON:uLabelLower";
                                                            }
                                                            
                                                            // FALLBACK TO LEGACY COLUMNS (if JSON is empty)
                                                            // 7. Direct Column match by Code
                                                            if (!$val) {
                                                                $val = $log->$code ?? null;
                                                                if ($val) $foundAt = "COL:code";
                                                            }
                                                            
                                                            // 8. Legacy Column by mapped key
                                                            if (!$val && isset($legacyMap[$label])) {
                                                                $lKey = $legacyMap[$label];
                                                                $val = $log->$lKey ?? null;
                                                                if ($val) $foundAt = "COL:legacyMap($lKey)";
                                                            }
                                                            
                                                            // 9. Try underscored code as column
                                                            if (!$val) {
                                                                $uCode = str_replace([' ', '.', '/'], '_', $code);
                                                                if (property_exists($log, $uCode)) {
                                                                    $val = $log->$uCode;
                                                                    if ($val) $foundAt = "COL:uCode";
                                                                }
                                                            }
                                                        @endphp
                                                        @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                                        <tr>
                                                            <td class="ps-3">{{ $displayLabel }}</td>
                                                            <td class="text-center">
                                                                @include('admin.reports.partials.badge', ['status' => $val ?: '-'])
                                                                @if($foundAt)
                                                                    <small class="text-muted d-block" style="font-size:8px;">{{ $foundAt }}</small>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach

                                            {{-- Unmapped items (Last) --}}
                                            @if(!empty($unmapped))
                                                <tr class="table-secondary"><th colspan="2">ADDITIONAL ITEMS</th></tr>
                                                @foreach($unmapped as $k => $v)
                                                    <tr>
                                                        <td class="ps-3">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                                                        <td class="text-center">@include('admin.reports.partials.badge', ['status' => $v])</td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION D: IBOX -->
                                            <tr class="table-secondary"><th colspan="2">D. IBOX SYSTEM</th></tr>
                                            <tr><td class="ps-3">IBOX Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td></tr>
                                            <tr><td class="ps-3">Pressure (Digital)</td><td class="text-center">{{ $log->ibox_pressure ?? '-' }}</td></tr>
                                            <tr><td class="ps-3">Temperature</td><td class="text-center">{{ $log->ibox_temperature ?? '-' }}</td></tr>
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION E: INSTRUMENTS -->
                                            <tr class="table-secondary"><th colspan="2">E. INSTRUMENTS</th></tr>
                                            <tr><td class="ps-3">Pressure Gauge</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td></tr>
                                            <tr><td class="ps-3">Level Gauge</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td></tr>
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION F: VACUUM -->
                                            <tr class="table-secondary"><th colspan="2">F. VACUUM SYSTEM</th></tr>
                                            <tr><td class="ps-3">Vacuum Gauge</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td></tr>
                                            <tr><td class="ps-3">Port Suction</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition])</td></tr>
                                            <tr><td class="ps-3">Value</td><td class="text-center fw-bold">{{ $log->vacuum_value ? (float)$log->vacuum_value . ' mTorr' : '-' }}</td></tr>
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION G: PSV -->
                                            <tr class="table-secondary"><th colspan="2">G. PSV</th></tr>
                                            @foreach(['psv1', 'psv2', 'psv3', 'psv4'] as $p)
                                                @if($log->{$p.'_condition'}) {{-- Only show if data exists (legacy friendly) --}}
                                                <tr>
                                                    <td class="ps-3">{{ strtoupper($p) }} Condition</td>
                                                    <td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->{$p.'_condition'}])</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                            @endif
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
