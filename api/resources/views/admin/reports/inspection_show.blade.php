@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Isotank Details</h2>
        <span class="text-white">{{ $isotank->iso_number }}</span>
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

<div class="row align-items-start">
    <!-- LEFT: Overview Card -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4 bg-dark text-white border-secondary">
            <div class="card-header">Overview</div>
            <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-0">
                    <tr><th class="text-white">ISO Number</th><td class="fw-bold text-white">{{ $isotank->iso_number }}</td></tr>
                    <tr><th class="text-white">Owner</th><td class="text-white">{{ $isotank->owner ?? '-' }}</td></tr>
                    <tr><th class="text-white">Location</th><td class="text-white">{{ $isotank->location ?? '-' }}</td></tr>
                    <tr><th class="text-white">Product</th><td class="text-white">{{ $isotank->product ?? '-' }}</td></tr>
                    <tr><th class="text-white">Filling Status</th>
                        <td>
                            <span class="badge {{ $isotank->filling_status_code=='filled'?'bg-success':'bg-secondary' }} text-white">
                                {{ $isotank->filling_status_desc ?? $isotank->filling_status_code ?? 'Empty' }}
                            </span>
                        </td>
                    </tr>
                    <tr><th class="text-white">Status</th><td class="text-white">{{ ucfirst($isotank->status) }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-4 bg-dark text-white border-secondary">
            <div class="card-header">Technical Specs</div>
            <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-0">
                    <tr><th class="text-white">Manufacturer</th><td class="text-white">{{ $isotank->manufacturer ?? '-' }}</td></tr>
                    <tr><th class="text-white">Serial No</th><td class="text-white">{{ $isotank->manufacturer_serial_number ?? '-' }}</td></tr>
                    <tr><th class="text-white">Model Type</th><td class="text-white">{{ $isotank->model_type ?? '-' }}</td></tr>
                    <tr><th class="text-white">Capacity</th><td class="text-white">{{ $isotank->capacity ? $isotank->capacity.' L' : '-' }}</td></tr>
                    <tr><th class="text-white">Tare Weight</th><td class="text-white">{{ $isotank->tare_weight ? $isotank->tare_weight.' Kg' : '-' }}</td></tr>
                    <tr><th class="text-white">Max Gross</th><td class="text-white">{{ $isotank->max_gross_weight ? $isotank->max_gross_weight.' Kg' : '-' }}</td></tr>
                </table>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4 bg-dark text-white border-secondary">
             <div class="card-header">Certificates & Dates</div>
             <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-0">
                    <tr><th class="text-white">Init Pressure Test</th><td class="text-white">{{ $isotank->initial_pressure_test_date ? $isotank->initial_pressure_test_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><th class="text-white">CSC Init Test</th><td class="text-white">{{ $isotank->csc_initial_test_date ? $isotank->csc_initial_test_date->format('d/m/Y') : '-' }}</td></tr>
                     <tr><td colspan="2"><hr class="my-1"></td></tr>
                    <tr><th class="text-white">Class Expiry</th><td class="fw-bold {{ $isotank->class_survey_expiry_date && $isotank->class_survey_expiry_date->isPast() ? 'text-danger' : 'text-white' }}">{{ $isotank->class_survey_expiry_date ? $isotank->class_survey_expiry_date->format('d/m/Y') : '-' }}</td></tr>
                    <tr><th class="text-white">CSC Expiry</th><td class="fw-bold {{ $isotank->csc_survey_expiry_date && $isotank->csc_survey_expiry_date->isPast() ? 'text-danger' : 'text-white' }}">{{ $isotank->csc_survey_expiry_date ? $isotank->csc_survey_expiry_date->format('d/m/Y') : '-' }}</td></tr>
                </table>
             </div>
        </div>
    </div>

    <!-- RIGHT: Tabs for History -->
    <div class="col-md-8">
        <style>
            .nav-tabs .nav-link { color: #aaa; border: 1px solid transparent; }
            .nav-tabs .nav-link:hover { color: #fff; border-color: #444; }
            .nav-tabs .nav-link.active { background-color: #343a40 !important; color: #fff !important; border-color: #6c757d #6c757d #343a40 !important; }
            .nav-tabs { border-bottom: 1px solid #6c757d; }
        </style>
        <ul class="nav nav-tabs mb-3 border-secondary" id="historyTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#condition">Condition</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inspections">Inspections</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#maintenance">Maintenance</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#calib">Calibration</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vacuum">Vacuum</button></li>
        </ul>

        <div class="tab-content">
            <!-- Latest Condition -->
            <div class="tab-pane fade show active" id="condition">
                 @if(isset($log) || $isotank->latestInspection)
                    <div class="card shadow-sm bg-dark text-white border-secondary">
                        <div class="card-body">
                            @php 
                                // Priority: Passed $log > Latest Inspection Log
                                if (!isset($log)) {
                                     $log = $isotank->latestInspection->lastInspectionLog ?? $isotank->latestInspection;
                                }
                            @endphp
                            <h5 class="text-white">Inspection Date: {{ $log->created_at->format('d M Y') }}</h5>
                            <p class="text-white">Inspector: {{ $log->inspector->name ?? $log->inspector_name ?? '-' }}</p>
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-group">
                                        <li class="list-group-item bg-dark bg-opacity-50 border-secondary d-flex justify-content-between"><span class="text-white">Vacuum</span> <strong class="text-white">{{ $log->vacuum_value ? (float)$log->vacuum_value : '-' }}</strong></li>
                                        <li class="list-group-item bg-dark bg-opacity-50 border-secondary d-flex justify-content-between"><span class="text-white">Pressure</span> <strong class="text-white">{{ $log->pressure_1 ? (float)$log->pressure_1 : '-' }}</strong></li>
                                        <li class="list-group-item bg-dark bg-opacity-50 border-secondary d-flex justify-content-between"><span class="text-white">Level</span> <strong class="text-white">{{ $log->level_1 ? (float)$log->level_1 : '-' }}</strong></li>
                                    </ul>
                                </div> <!-- Close col-6 -->
                            </div> <!-- Close internal row -->
                            <div class="mt-4">
                                <h5 class="text-white border-bottom border-secondary pb-2">Items Condition</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-dark border-secondary">
                        <thead>
                                            <tr>
                                                <th class="text-white">Category / Item Name</th>
                                                <th class="text-center text-white">Condition/Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $inspectionItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order')->get();
                                                $logData = is_array($log->inspection_data) ? $log->inspection_data : json_decode($log->inspection_data, true) ?? [];
                                                $tankCat = $isotank->tank_category ?? 'T75'; // Default to T75

                                                // Legacy Map for Fallback (Synchronized with Report View)
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
                                                $grouped = $catSpecificItems->groupBy('category')->sortKeys();
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
                                                    <tr class="table-secondary"><th colspan="2" class="text-white">{{ $categoryMap[$categoryName] ?? strtoupper($categoryName) }}</th></tr>
                                                    @foreach($items as $item)
                                                        @php 
                                                            $code = $item->code; 
                                                            $label = $item->label;
                                                            
                                                            // EXACT COPY FROM inspection_show.blade.php (WORKING VERSION)
                                                            // PRO ROBUST LOOKUP STRATEGY
                                                            // 1. Direct Code match in JSON
                                                            $val = $logData[$code] ?? null;
                                                            
                                                            // 2. Direct Column match
                                                            if (!$val) $val = $log->$code ?? null;

                                                            // FIX: Port Suction Condition Column Mismatch
                                                            if (!$val && $code === 'port_suction_condition') {
                                                                $val = $log->vacuum_port_suction_condition ?? null;
                                                            }
                                                            
                                                            // 3. Underscore-version of Code in JSON
                                                            if (!$val) {
                                                                $uCode = str_replace([' ', '.', '/'], '_', $code);
                                                                $val = $logData[$uCode] ?? null;
                                                            }
                                                            
                                                            // 4. Legacy Map (By Label)
                                                            if (!$val && isset($legacyMap[$label])) {
                                                                $lKey = $legacyMap[$label];
                                                                $val = $logData[$lKey] ?? ($log->$lKey ?? null);
                                                            }
                                                            
                                                            // 5. Underscore-version of Label in JSON
                                                            // FIX: Check for Legacy Label as Key (e.g. "GPS_4G_LP_LAN_Antenna")
                                                            if (!$val) {
                                                                $uLabel = str_replace([' ', '.', '/'], '_', $label); // Try literal label with underscores
                                                                $val = $logData[$uLabel] ?? null;
                                                            }
                                                            // FIX: Try exact label too
                                                            if (!$val) {
                                                                 $val = $logData[$label] ?? null;
                                                            }

                                                            if (!$val) {
                                                                $uLabelLower = str_replace([' ', '.', '/'], '_', strtolower($label));
                                                                $val = $logData[$uLabelLower] ?? null;
                                                            }

                                                            // 6. Direct Label Match (Spaces preserved)
                                                            if (!$val) {
                                                                $val = $logData[$label] ?? null;
                                                            }
                                                        @endphp
                                                        @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                                        <tr>
                                                            <td class="ps-3 text-white">{{ $displayLabel }}</td>
                                                            <td class="text-center">
                                                                @include('admin.reports.partials.badge', ['status' => $val ?: '-'])
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach

                                            {{-- Unmapped items (Last) --}}
                                            @if(!empty($unmapped))
                                                <tr class="table-secondary"><th colspan="2" class="text-white">ADDITIONAL ITEMS</th></tr>
                                                @foreach($unmapped as $k => $v)
                                                    <tr>
                                                        <td class="ps-3">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                                                        <td class="text-center">@include('admin.reports.partials.badge', ['status' => $v])</td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION D: IBOX -->
                                            <tr class="table-secondary"><th colspan="2" class="text-white">D. IBOX SYSTEM</th></tr>
                                            <tr><td class="ps-3">IBOX Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td></tr>
                                            <tr><td class="ps-3">Battery</td><td class="text-center">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.' %' : '-' }}</td></tr>
                                            <tr><td class="ps-3">Pressure (Digital)</td><td class="text-center">{{ $log->ibox_pressure ? $log->ibox_pressure.' MPa' : '-' }}</td></tr>
                                            <tr><td class="ps-3">Temperature #1 (Digital)</td><td class="text-center">{{ $log->ibox_temperature_1 ?? $log->ibox_temperature ?? '-' }}</td></tr>
                                            @if($log->ibox_temperature_1_timestamp || $log->ibox_temperature_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Temp 1)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->ibox_temperature_1_timestamp ?? $log->ibox_temperature_timestamp }}</td></tr>
                                            @endif
                                            <tr><td class="ps-3">Temperature #2 (Digital)</td><td class="text-center">{{ $log->ibox_temperature_2 ?? '-' }}</td></tr>
                                            @if($log->ibox_temperature_2_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Temp 2)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->ibox_temperature_2_timestamp }}</td></tr>
                                            @endif
                                            <tr><td class="ps-3">Level</td><td class="text-center">{{ $log->ibox_level ? $log->ibox_level.' %' : '-' }}</td></tr>
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION E: INSTRUMENTS -->
                                            <tr class="bg-dark bg-opacity-50"><th colspan="2" class="text-white ps-3">E. INSTRUMENTS</th></tr>
                                            <tr><td class="ps-3">Pressure Gauge Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td></tr>
                                            @if($log->pressure_gauge_serial_number)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Serial Number</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->pressure_gauge_serial_number }}</td></tr>
                                            @endif
                                            <tr><td class="ps-3">Reading (Pressure 1)</td><td class="text-center">{{ $log->pressure_1 ?? '-' }}</td></tr>
                                            @if($log->pressure_1_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Pressure 1)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->pressure_1_timestamp }}</td></tr>
                                            @endif
                                            <tr><td class="ps-3">Reading (Pressure 2)</td><td class="text-center">{{ $log->pressure_2 ?? '-' }}</td></tr>
                                            @if($log->pressure_2_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Pressure 2)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->pressure_2_timestamp }}</td></tr>
                                            @endif

                                            <tr><td class="ps-3">Level Gauge Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td></tr>
                                            <tr><td class="ps-3">Reading (Level 1)</td><td class="text-center">{{ $log->level_1 ?? '-' }}</td></tr>
                                            @if($log->level_1_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Level 1)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->level_1_timestamp }}</td></tr>
                                            @endif
                                            <tr><td class="ps-3">Reading (Level 2)</td><td class="text-center">{{ $log->level_2 ?? '-' }}</td></tr>
                                            @if($log->level_2_timestamp)
                                            <tr><td class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;"> — Time (Level 2)</td><td class="text-center text-muted" style="font-size:0.85em">{{ $log->level_2_timestamp }}</td></tr>
                                            @endif
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION F: VACUUM -->
                                            <!-- SECTION F: VACUUM -->
                                            <tr class="bg-dark bg-opacity-50"><th colspan="2" class="text-white ps-3">F. VACUUM SYSTEM</th></tr>
                                            <tr><td class="ps-3">Vacuum Gauge</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td></tr>
                                            <tr><td class="ps-3">Port Suction</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition ?? $logData['port_suction_condition'] ?? $logData['Port Suction Condition'] ?? null])</td></tr>
                                            <tr><td class="ps-3">Value</td><td class="text-center fw-bold">{{ $log->vacuum_value ? (float)$log->vacuum_value . ' mTorr' : '-' }}</td></tr>
                                            <tr><td class="ps-3">Vacuum Temperature</td><td class="text-center">{{ $log->vacuum_temperature ? $log->vacuum_temperature.' °C' : '-' }}</td></tr>
                                            <tr><td class="ps-3">Check Datetime</td><td class="text-center">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('d M Y H:i') : '-' }}</td></tr>
                                            @endif

                                            @if($tankCat == 'T75')
                                            <!-- SECTION G: PSV -->
                                            <tr class="bg-dark bg-opacity-50"><th colspan="2" class="text-white ps-3">G. PSV</th></tr>
                                            @foreach(['psv1', 'psv2', 'psv3', 'psv4'] as $p)
                                                @if($log->{$p.'_condition'}) {{-- Only show if data exists (legacy friendly) --}}
                                                <tr>
                                                    <td class="ps-3">{{ strtoupper($p) }} Condition</td>
                                                    <td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->{$p.'_condition'}])</td>
                                                </tr>
                                                @if($log->{$p.'_serial_number'} || $log->{$p.'_calibration_date'})
                                                <tr>
                                                    <td colspan="2" class="ps-3 text-muted" style="font-size:0.85em; padding-left: 20px !important;">
                                                        SN: {{ $log->{$p.'_serial_number'} ?? '-' }} | 
                                                        Cal: {{ $log->{$p.'_calibration_date'} ?? '-' }} | 
                                                        Valid: {{ $log->{$p.'_valid_until'} ?? '-' }}
                                                    </td>
                                                </tr>
                                                @endif
                                                @endif
                                            @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                                <a href="{{ route('admin.reports.inspection.pdf', $log->id) }}" class="btn btn-danger btn-sm" target="_blank"><i class="bi bi-file-pdf"></i> Download PDF</a>
                            </div>
                        </div>
                 @else
                    <div class="alert alert-warning">No detailed inspection data available yet.</div>
                 @endif
            </div>

            <!-- Inspection History -->
            <div class="tab-pane fade" id="inspections">
                <div class="card shadow-sm bg-dark text-white border-secondary"><div class="card-body p-0">
                <table class="table table-hover table-dark mb-0">
                    <thead><tr><th>Date</th><th>Type</th><th>Inspector</th><th>Status</th><th>PDF</th></tr></thead>
                    <tbody>
                        @forelse($inspections as $ins)
                        <tr>
                            <td>{{ $ins->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ strtoupper(str_replace('_',' ', $ins->inspection_type)) }}</td>
                            <td>{{ $ins->inspector->name ?? '-' }}</td>
                            <td>{{ $ins->filling_status_desc }}</td>
                            <td>
                                @if($ins->pdf_path) <a href="{{ route('admin.reports.inspection.pdf', $ins->id) }}" target="_blank" class="btn btn-xs btn-danger"><i class="bi bi-pdf"></i> PDF</a> @endif
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
                 <div class="card shadow-sm bg-dark text-white border-secondary"><div class="card-body p-0">
                <table class="table table-hover table-dark mb-0">
                    <thead><tr><th>Date</th><th>Item / Component</th><th>Status</th><th>Technician</th><th>Desc</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($maintenance as $job)
                        <tr>
                            <td>{{ $job->created_at->format('Y-m-d') }}</td>
                            <td>{{ $job->source_item ?? 'General' }}</td>
                            <td>
                                @if(in_array($job->status, ['closed', 'completed']))
                                    <span class="badge bg-success">CLOSED</span>
                                @elseif($job->status == 'deferred')
                                    <span class="badge bg-secondary">DEFERRED</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ strtoupper(str_replace('_', ' ', $job->status)) }}</span>
                                @endif
                            </td>
                            <td>{{ $job->completedBy->name ?? '-' }}</td>
                            <td>{{ Str::limit($job->description, 50) }}</td>
                            <td>
                                <a href="{{ route('admin.reports.maintenance.show', $job->id) }}" class="btn btn-xs btn-info" target="_blank" title="View Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">No maintenance history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                 </div></div>
            </div>
            
              <div class="tab-pane fade" id="calib">
                  <div class="card shadow-sm bg-dark text-white border-secondary"><div class="card-body">
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
                          <table class="table table-bordered table-striped table-dark border-secondary">
                              <thead>
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
                   <div class="card shadow-sm bg-dark text-white border-secondary"><div class="card-body p-0">
                    <table class="table table-hover table-dark mb-0">
                        <thead><tr><th>Check Date</th><th>Value</th><th>Temp</th><th>Remark</th></tr></thead>
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
