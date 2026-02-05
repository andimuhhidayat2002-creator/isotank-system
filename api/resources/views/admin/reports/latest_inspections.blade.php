@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
    <div class="btn-group">
        <button id="exportExcelBtn" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </button>
        <button id="exportPdfBtn" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </button>
    </div>
</div>

    <!-- Category Filter -->
    <ul class="nav nav-pills mb-3">

        <li class="nav-item">
          <a class="nav-link {{ $category == 'T75' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T75']) }}">T75</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T11' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T11']) }}">T11</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $category == 'T50' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T50']) }}">T50</a>
        </li>
    </ul>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="latestConditionTable" class="table table-bordered table-sm align-middle text-nowrap" style="font-size: 0.75rem;">
                <thead class="text-white text-center">
                    <tr>
                        <th rowspan="2" class="align-middle bg-secondary bg-opacity-75" style="width: 120px;">ISO NUMBER</th>
                        <th rowspan="2" class="align-middle bg-secondary bg-opacity-75" style="width: 100px;">UPDATED AT</th>
                        
                        {{-- DYNAMIC CATEGORY HEADERS --}}
                        @php 
                            $colorToggle = true; 
                            $tCat = $category ?? 'T75';
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
                        @foreach($groupedItems as $catName => $items)
                            <th colspan="{{ $items->count() }}" class="{{ $colorToggle ? 'bg-primary' : 'bg-success bg-opacity-75' }} text-white" style="border-bottom: 2px solid white;">
                                {{ $categoryMap[$catName] ?? strtoupper($catName) }}
                            </th>
                            @php $colorToggle = !$colorToggle; @endphp
                        @endforeach
                        
                        {{-- HARDCODED SECTIONS (LEGACY T75) --}}
                        @if($category === 'all' || $category === 'T75')
                            <th colspan="5" style="background-color: #F59E0B; color: white; border-bottom: 2px solid white;">IBOX</th>
                            <th colspan="6" style="background-color: #3B82F6; color: white; border-bottom: 2px solid white;">INSTRUMENTS</th>
                            <th colspan="5" style="background-color: #EF4444; color: white; border-bottom: 2px solid white;">VACUUM</th>
                            <th colspan="12" class="bg-secondary bg-opacity-75 text-white" style="border-bottom: 2px solid white;">PSV</th>
                        @endif
                    </tr>
                    <tr class="vertical-headers">
                        {{-- DYNAMIC ITEM HEADERS --}}
                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item) 
                                @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                <th class="text-white"><div>{{ $displayLabel }}</div></th> 
                            @endforeach
                        @endforeach
                        
                        {{-- HARDCODED SUB HEADERS --}}
                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX -->
                            <th class="text-white"><div>Condition</div></th>
                            <th class="text-white"><div>Battery</div></th>
                            <th class="text-white"><div>Pressure</div></th>
                            <th class="text-white"><div>Temperature</div></th>
                            <th class="text-white"><div>Level</div></th>
                            
                            <!-- Instruments -->
                            <th class="text-white"><div>PG Cond.</div></th>
                            <th class="text-white"><div>PG Serial</div></th>
                            <th class="text-white"><div>PG Calib.</div></th>
                            <th class="text-white"><div>Pressure</div></th>
                            <th class="text-white"><div>LG Cond.</div></th>
                            <th class="text-white"><div>Level</div></th>
                            
                            <!-- Vacuum -->
                            <th class="text-white"><div>VG Cond.</div></th>
                            <th class="text-white"><div>Port Suction</div></th>
                            <th class="text-white"><div>Value</div></th>
                            <th class="text-white"><div>Temp</div></th>
                            <th class="text-white"><div>Check Date</div></th>
                            
                            <!-- PSV -->
                            <th class="text-white"><div>PSV1 Cond</div></th><th class="text-white"><div>Serial</div></th><th class="text-white"><div>Date</div></th>
                            <th class="text-white"><div>PSV2 Cond</div></th><th class="text-white"><div>Serial</div></th><th class="text-white"><div>Date</div></th>
                            <th class="text-white"><div>PSV3 Cond</div></th><th class="text-white"><div>Serial</div></th><th class="text-white"><div>Date</div></th>
                            <th class="text-white"><div>PSV4 Cond</div></th><th class="text-white"><div>Serial</div></th><th class="text-white"><div>Date</div></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php 
                        $iLog = $log->lastInspectionLog;
                        $logData = ($iLog && $iLog->inspection_data) 
                             ? (is_array($iLog->inspection_data) ? $iLog->inspection_data : json_decode($iLog->inspection_data, true))
                             : [];
                    @endphp
                    <tr class="text-center">
                        <td class="fw-bold text-start sticky-col">
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-decoration-none text-info" target="_blank">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </td>
                        <td class="small text-white">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
                        
                        {{-- DYNAMIC VALUES --}}
                        @php
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
                        @endphp

                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item)
                                @php 
                                    $code = $item->code; 
                                    $label = $item->label;
                                    
                                    // PRO ROBUST LOOKUP STRATEGY
                                    // 1. Direct Code match in JSON
                                    $val = $logData[$code] ?? null;
                                    
                                    // 2. Direct Column match
                                    if (!$val) $val = $iLog->$code ?? ($log->$code ?? null);

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
                                        $val = $logData[$lKey] ?? ($iLog->$lKey ?? ($log->$lKey ?? null));
                                    }
                                    
                                    // 5. Underscore-version of Label in JSON
                                    if (!$val) {
                                        $uLabel = str_replace([' ', '.', '/'], '_', strtolower($label));
                                        $val = $logData[$uLabel] ?? null;
                                    }
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach

                        {{-- HARDCODED VALUES (LEGACY T75) --}}
                        @if($category === 'all' || $category === 'T75')
                             {{-- IBOX --}}
                             <td>@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td>
                             <td class="text-white">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.'%' : '-' }}</td>
                             <td class="text-white">{{ $log->ibox_pressure ?? '-' }}</td>
                             <td class="text-white">{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                             <td class="text-white">{{ $log->ibox_level ?? '-' }}</td>

                            {{-- INSTRUMENTS --}}
                            <td>@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td>
                             @php
                                $comps = $log->isotank->components ?? collect();
                                $pgComp = $comps->where('component_type', 'PG')->first();
                                $pgDate = $log->pressure_gauge_calibration_date 
                                    ? \Carbon\Carbon::parse($log->pressure_gauge_calibration_date)->format('y-m-d')
                                    : ($pgComp && $pgComp->last_calibration_date ? $pgComp->last_calibration_date->format('y-m-d') : '-');
                                $pgSerial = $log->pressure_gauge_serial_number ?: ($pgComp ? $pgComp->serial_number : '-');
                            @endphp
                            <td class="small text-white">{{ $pgSerial }}</td>
                            <td class="small text-white">{{ $pgDate }}</td>
                            <td class="text-white">{{ $log->pressure_1 ? (float)$log->pressure_1 : '' }}</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td>
                            <td class="text-white">{{ $log->level_1 ? (float)$log->level_1 : '' }}</td>

                            {{-- VACUUM --}}
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition ?? $logData['port_suction_condition'] ?? $logData['Port Suction Condition'] ?? null])</td>
                            <td class="text-white">{{ $log->vacuum_value ? (float)$log->vacuum_value : '-' }}</td>
                            <td class="text-white">{{ $log->vacuum_temperature ?? '-' }}</td>
                            <td class="small text-white">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-' }}</td>

                            {{-- PSV --}}
                            @php
                                $getPsv = function($pos) use ($log, $comps) {
                                    $psvLogCond = $log->{"psv{$pos}_condition"};
                                    $psvLogSerial = $log->{"psv{$pos}_serial_number"};
                                    $psvLogDate = $log->{"psv{$pos}_calibration_date"};
                                    
                                    $comp = $comps->where('component_type', 'PSV')->where('position_code', $pos)->first();
                                    
                                    $serial = $psvLogSerial ?: ($comp->serial_number ?? '-');
                                    $date = $psvLogDate 
                                        ? \Carbon\Carbon::parse($psvLogDate)->format('y-m-d')
                                        : ($comp && $comp->last_calibration_date ? $comp->last_calibration_date->format('y-m-d') : '-');
                                        
                                    return [$psvLogCond, $serial, $date];
                                };
                                
                                $p1 = $getPsv(1); $p2 = $getPsv(2); $p3 = $getPsv(3); $p4 = $getPsv(4);
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $p1[0]])</td>
                            <td class="small text-white">{{ $p1[1] }}</td>
                            <td class="small text-white">{{ $p1[2] }}</td>
                            
                            <td>@include('admin.reports.partials.badge', ['status' => $p2[0]])</td>
                            <td class="small text-white">{{ $p2[1] }}</td>
                            <td class="small text-white">{{ $p2[2] }}</td>
                            
                            <td>@include('admin.reports.partials.badge', ['status' => $p3[0]])</td>
                            <td class="small text-white">{{ $p3[1] }}</td>
                            <td class="small text-white">{{ $p3[2] }}</td>
                            
                            <td>@include('admin.reports.partials.badge', ['status' => $p4[0]])</td>
                            <td class="small text-white">{{ $p4[1] }}</td>
                            <td class="small text-white">{{ $p4[2] }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th>ISO</th><th>Upd</th>
                         @foreach($groupedItems as $catName => $items)
                             @foreach($items as $item) 
                                 @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                 <th>{{ substr($displayLabel,0,4) }}</th> 
                             @endforeach
                         @endforeach

                         @if($category === 'all' || $category === 'T75')
                             <!-- IBOX -->
                             <th>Cond</th><th>Bat</th><th>Prs</th><th>Tmp</th><th>Lvl</th>
                             <!-- Inst -->
                             <th>PGC</th><th>SN</th><th>Cal</th><th>Prs</th><th>LGC</th><th>Lvl</th>
                             <!-- Vac -->
                             <th>VC</th><th>VPC</th><th>Val</th><th>Tmp</th><th>Dt</th>
                             <!-- PSV -->
                             <th>P1C</th><th>SN</th><th>Dt</th>
                             <th>P2C</th><th>SN</th><th>Dt</th>
                             <th>P3C</th><th>SN</th><th>Dt</th>
                             <th>P4C</th><th>SN</th><th>Dt</th>
                         @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#latestConditionTable tfoot th').each(function() {
        $(this).html('<input type="text" class="form-control form-control-sm" style="min-width: 40px;" placeholder="" />');
    });

    var table = $('#latestConditionTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { 
                extend: 'excelHtml5', 
                className: 'btn btn-success btn-sm mb-3 d-none', // Hidden, triggered by custom button
                title: 'Latest_Isotank_Condition',
                exportOptions: {
                    orthogonal: 'export'
                }
            },
            { 
                extend: 'pdfHtml5', 
                className: 'btn btn-danger btn-sm mb-3 d-none', // Hidden, triggered by custom button
                orientation: 'landscape', 
                pageSize: 'A3',
                title: 'Latest Isotank Condition',
                exportOptions: {
                    orthogonal: 'export'
                }
            }
        ],
        pageLength: 50,
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

    // Connect custom buttons to DataTables export
    $('#exportExcelBtn').on('click', function() {
        table.button('.buttons-excel').trigger();
    });

    $('#exportPdfBtn').on('click', function() {
        table.button('.buttons-pdf').trigger();
    });
});
</script>
@endpush

<style>
    th { font-size: 0.65rem; text-transform: uppercase; }
    .dataTables_wrapper .dataTables_filter { text-align: left; }
    .vertical-headers th { height: 140px; vertical-align: bottom; padding-bottom: 15px !important; position: relative; }
    .vertical-headers th div { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; margin: 0 auto; width: 100%; text-align: left; }
</style>
@endsection
