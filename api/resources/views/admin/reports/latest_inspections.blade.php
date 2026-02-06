@extends('layouts.app')

@section('content')
<!-- FIXED COLUMNS CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
<!-- Buttons CSS is already imported in app.js SCSS usually, but we keep FixedColumns CSS as it might be missing -->

<!-- CSS overrides remain here -->

<style>
/* ... existing styles ... */
</style>

<!-- ... HTML Content ... -->

@push('scripts')
<script>
// Polling function to wait for Vite bundle (jQuery + Dependencies)
function waitForDependencies(callback) {
    if (window.jQuery && $.fn.DataTable && window.JSZip && window.pdfMake) {
        callback();
    } else {
        console.log('Waiting for libraries...');
        setTimeout(() => waitForDependencies(callback), 100);
    }
}

waitForDependencies(function() {
    console.log('Dependencies loaded. Initializing Table...');
    
    // Ensure DataTable is not already initialized
    if ($.fn.DataTable.isDataTable('#latestConditionTable')) {
        $('#latestConditionTable').DataTable().destroy();
    }

    // Initialize DataTable
    var table = $('#latestConditionTable').DataTable({
        dom: 'Brtip', 
        buttons: [
            { 
                extend: 'excelHtml5', 
                className: 'buttons-excel', 
                title: 'Latest_Isotank_Condition_' + new Date().toISOString().split('T')[0], 
                exportOptions: { 
                    orthogonal: 'export',
                    format: {
                        body: function ( data, row, column, node ) {
                            return data ? String(data).replace(/<[^>]+>/g, "").trim() : "";
                        }
                    }
                } 
            },
            { 
                extend: 'pdfHtml5', 
                className: 'buttons-pdf', 
                orientation: 'landscape', 
                pageSize: 'A1', 
                title: 'Latest Isotank Condition', 
                exportOptions: { 
                    orthogonal: 'export',
                    format: {
                        body: function ( data, row, column, node ) {
                            return data ? String(data).replace(/<[^>]+>/g, "").trim() : "";
                        }
                    }
                }, 
                customize: function(doc) { 
                    doc.defaultStyle.fontSize = 5; 
                    doc.styles.tableHeader.fontSize = 6;
                    // Auto-width adjustment for strict alignment
                    var colCount = doc.content[1].table.body[0].length;
                    doc.content[1].table.widths = Array(colCount).fill('*');
                } 
            }
        ],
        fixedColumns: { left: 2 },
        scrollX: true,
        ordering: false,
        pageLength: 20,
        searching: true,
        autoWidth: false
    });

    // Hide default buttons container
    $('.dt-buttons').hide();

    // Bind Custom Inputs
    $('#btnExportExcel').off('click').on('click', function() { 
        table.button('.buttons-excel').trigger(); 
    });
    
    $('#btnExportPdf').off('click').on('click', function() { 
        table.button('.buttons-pdf').trigger(); 
    });

    $('#customSearch').off('keyup change').on('keyup change', function() { 
        table.search(this.value).draw(); 
    });

    // Drag Scroll
    const slider = document.querySelector('.dataTables_scrollBody');
    if(slider) {
        let isDown = false, startX, scrollLeft;
        slider.style.cursor = 'grab';
        slider.addEventListener('mousedown', (e) => { isDown=true; slider.style.cursor='grabbing'; startX=e.pageX-slider.offsetLeft; scrollLeft=slider.scrollLeft; });
        slider.addEventListener('mouseleave', () => { isDown=false; slider.style.cursor='grab'; });
        slider.addEventListener('mouseup', () => { isDown=false; slider.style.cursor='grab'; });
        slider.addEventListener('mousemove', (e) => { if(!isDown) return; e.preventDefault(); const x=e.pageX-slider.offsetLeft; const walk=(x-startX)*2; slider.scrollLeft=scrollLeft-walk; });
    }
});
</script>
@endpush
@endsection

<style>
    /* 1. VISIBILITY FIX: Force White Text on Dark Background */
    #latestConditionTable { font-size: 0.75rem !important; }
    #latestConditionTable th, #latestConditionTable td { padding: 5px 6px !important; vertical-align: middle; }
    
    #latestConditionTable tbody td { color: #ffffff !important; }
    #latestConditionTable tbody td .text-muted { color: #cccccc !important; }
    #latestConditionTable tbody td a { color: #3dd5f3 !important; } /* Cyan for links */

    /* Dark Zebra Striping */
    #latestConditionTable tbody tr:nth-of-type(odd) td { background-color: #2c2c2c !important; }
    #latestConditionTable tbody tr:nth-of-type(even) td { background-color: #222222 !important; }

    /* Fixed Columns Darker */
    table.dataTable tbody tr td.dtfc-fixed-left { background-color: #1a1a1a !important; color: #fff !important; z-index: 10; border-right: 1px solid #444; }
    table.dataTable thead tr th.dtfc-fixed-left { background-color: #333 !important; z-index: 20; }
    
    /* Layout */
    .vertical-headers th { height: 140px; vertical-align: bottom; }
    .vertical-headers th div { writing-mode: vertical-rl; transform: rotate(180deg); width: 100%; }
    
    /* Hide Default Buttons Interface (We use custom buttons) */
    .dt-buttons { display: none !important; } 
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
    
    <!-- MANUAL BUTTONS -->
    <div class="d-flex gap-2">
        <input type="text" id="customSearch" class="form-control" placeholder="Search data..." style="width: 250px;">
        <div class="btn-group">
            <button type="button" class="btn btn-success" id="btnExportExcel">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
            <button type="button" class="btn btn-danger" id="btnExportPdf">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ $category == 'T75' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T75']) }}">T75</a></li>
    <li class="nav-item"><a class="nav-link {{ $category == 'T11' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T11']) }}">T11</a></li>
    <li class="nav-item"><a class="nav-link {{ $category == 'T50' ? 'active' : '' }}" href="{{ route('admin.reports.latest', ['category' => 'T50']) }}">T50</a></li>
</ul>

<div class="card">
    <div class="card-body p-2">
        <div class="table-responsive"> 
            <table id="latestConditionTable" class="table table-bordered table-sm align-middle text-nowrap" style="width:100%">
                <thead class="text-white text-center">
                    <tr>
                        <th rowspan="2" class="align-middle bg-secondary bg-opacity-75" style="width: 120px;">ISO NUMBER</th>
                        <th rowspan="2" class="align-middle bg-secondary bg-opacity-75" style="width: 100px;">UPDATED AT</th>
                        @php 
                            $colorToggle = true; 
                            $tCat = $category ?? 'T75';
                            if ($tCat === 'T11') {
                                $categoryMap = ['a'=>'A. FRONT','b'=>'B. REAR','c'=>'C. RIGHT','d'=>'D. LEFT','e'=>'E. TOP','other'=>'Others'];
                            } elseif ($tCat === 'T50') {
                                $categoryMap = ['a'=>'A. FRONT','b'=>'B. REAR','c'=>'C. RIGHT','d'=>'D. LEFT','e'=>'E. TOP','other'=>'Others'];
                            } else {
                                $categoryMap = ['b'=>'B. GENERAL','c'=>'C. VALVES','d'=>'D. IBOX','e'=>'E. INST','f'=>'F. VACUUM','g'=>'G. PSV'];
                            }
                        @endphp
                        @foreach($groupedItems as $catName => $items)
                            @if(($tCat === 'T75' || $tCat === 'all') && in_array(strtolower($catName), ['d', 'e', 'f', 'g'])) @continue @endif
                            <th colspan="{{ $items->count() }}" class="{{ $colorToggle ? 'bg-primary' : 'bg-success bg-opacity-75' }} text-white">
                                {{ $categoryMap[$catName] ?? strtoupper($catName) }}
                            </th>
                            @php $colorToggle = !$colorToggle; @endphp
                        @endforeach
                        
                        @if($category === 'all' || $category === 'T75')
                            <th colspan="8" class="bg-warning text-dark">IBOX SYSTEM</th>
                            <th colspan="12" class="bg-info text-white">INSTRUMENT CHECK</th>
                            <th colspan="5" class="bg-danger text-white">VACUUM TEST</th>
                            <th colspan="12" class="bg-secondary text-white">PSV CHECK</th>
                        @endif
                    </tr>
                    <tr>
                        @foreach($groupedItems as $catName => $items)
                            @if(($tCat === 'T75' || $tCat === 'all') && in_array(strtolower($catName), ['d', 'e', 'f', 'g'])) @continue @endif
                            @foreach($items as $item) 
                                @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                <th class="vertical-headers"><div>{{ substr($displayLabel,0,25) }}</div></th> 
                            @endforeach
                        @endforeach
                        @if($category === 'all' || $category === 'T75')
                             <!-- IBOX -->
                             <th>Cond</th><th>Bat</th><th>Prs</th>
                             <th>Tmp 1</th><th>Ts 1</th>
                             <th>Tmp 2</th><th>Ts 2</th>
                             <th>Lvl</th>

                             <!-- INST -->
                             <th>PGC</th><th>SN</th><th>Cal</th>
                             <th>Prs 1</th><th>Ts 1</th>
                             <th>Prs 2</th><th>Ts 2</th>
                             <th>LGC</th>
                             <th>Lvl 1</th><th>Ts 1</th>
                             <th>Lvl 2</th><th>Ts 2</th>

                             <!-- VAC -->
                             <th>VC</th><th>VPC</th><th>Val</th><th>Tmp</th><th>Dt</th>
                             <!-- PSV -->
                             <th>P1C</th><th>SN</th><th>Dt</th>
                             <th>P2C</th><th>SN</th><th>Dt</th>
                             <th>P3C</th><th>SN</th><th>Dt</th>
                             <th>P4C</th><th>SN</th><th>Dt</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
@php 
                        $iLog = $log->lastInspectionLog;
                        $logData = ($iLog && $iLog->inspection_data) ? (is_array($iLog->inspection_data) ? $iLog->inspection_data : json_decode($iLog->inspection_data, true)) : [];
                        
                        $legacyMap = [
                            'Surface Condition' => 'surface', 'Tank Surface & Paint Condition' => 'surface',
                            'Frame Condition' => 'frame', 'Frame Structure' => 'frame',
                            'Tank Name Plate' => 'tank_plate', 'Data Plate' => 'tank_plate',
                            'Venting Pipe' => 'venting_pipe', 'Explosion Proof Cover' => 'explosion_proof_cover',
                            'Safety Label' => 'safety_label', 'DG 1972 GHS MSA_Safety_label' => 'safety_label',
                            'Document Container' => 'document_container', 'Valve Box Door' => 'valve_box_door',
                            'Grounding System' => 'grounding_system', 'Valve Condition' => 'valve_condition',
                            'Valve Position' => 'valve_position', 'Pipe Joint' => 'pipe_joint',
                            'Air Source Connection' => 'air_source_connection', 'ESDV' => 'esdv',
                            'Blind Flange' => 'blind_flange', 'PRV' => 'prv'
                        ];

                        $getVal = function($code, $label) use ($logData, $legacyMap, $log, $iLog) {
                            $val = $logData[$code] ?? null;
                            if (!$val) $val = $iLog->$code ?? ($log->$code ?? null);
                            if (!$val && $code === 'port_suction_condition') $val = $iLog->vacuum_port_suction_condition ?? ($log->vacuum_port_suction_condition ?? null);
                            if (!$val) { $uCode = str_replace([' ', '.', '/'], '_', $code); $val = $logData[$uCode] ?? null; }
                            if (!$val && isset($legacyMap[$label])) { $lKey = $legacyMap[$label]; $val = $logData[$lKey] ?? ($iLog->$lKey ?? ($log->$lKey ?? null)); }
                            if (!$val) { $uLabel = str_replace([' ', '.', '/'], '_', $label); $val = $logData[$uLabel] ?? null; }
                            if (!$val) { $uLabelLower = str_replace([' ', '.', '/'], '_', strtolower($label)); $val = $logData[$uLabelLower] ?? null; }
                            if (!$val) { $val = $logData[$label] ?? null; }
                            return $val;
                        };
                        
                        $getCol = function($keys) use ($iLog, $logData, $log) {
                            if(!is_array($keys)) $keys = [$keys];
                            foreach($keys as $k) {
                                if($iLog && isset($iLog->$k)) return $iLog->$k;
                                if(isset($logData[$k])) return $logData[$k];
                                $uK = str_replace(' ','_',$k);
                                if(isset($logData[$uK])) return $logData[$uK];
                                if(isset($log->$k)) return $log->$k;
                            }
                            return null;
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold text-start">
                            <a href="{{ route('admin.isotanks.show', $log->isotank_id) }}" class="text-decoration-none">
                                {{ $log->isotank->iso_number ?? 'UNKNOWN' }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </td>
                        <td class="small">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
@foreach($groupedItems as $catName => $items)
                             @if(($tCat === 'T75' || $tCat === 'all') && in_array(strtolower($catName), ['d', 'e', 'f', 'g'])) @continue @endif
                            @foreach($items as $item)
                                @php
                                    $val = $getVal($item->code, $item->label);
                                    if ($item->type === 'photo') $val = $val ? 'Uploaded' : 'Empty';
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach

                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX (With Robust Lookup) -->
                            @php
                                $ibox_cond = $getCol(['ibox_condition']);
                                $ibox_bat  = $getCol(['ibox_battery_percent', 'battery']);
                                $ibox_prs  = $getCol(['ibox_pressure', 'pressure']);
                                $ibox_tmp1 = $getCol(['ibox_temperature_1', 'ibox_temperature', 'temperature']);
                                $ibox_ts1  = $getCol(['ibox_temperature_1_timestamp', 'ibox_temperature_timestamp']);
                                
                                $ibox_tmp2 = $getCol(['ibox_temperature_2']);
                                $ibox_ts2  = $getCol(['ibox_temperature_2_timestamp']);
                                
                                $ibox_lvl  = $getCol(['ibox_level', 'level']);
                                
                                // Time Format Helper
                                $fmtTime = function($ts) { return $ts ? \Carbon\Carbon::parse($ts)->format('H:i') : '-'; };
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $ibox_cond])</td>
                            <td class="small">{{ $ibox_bat ? $ibox_bat.'%' : '-' }}</td>
                            <td class="small">{{ $ibox_prs ? $ibox_prs.' MPa' : '-' }}</td>
                            <td class="small">{{ $ibox_tmp1 ? $ibox_tmp1.' °C' : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($ibox_ts1) }}</td>
                            <td class="small">{{ $ibox_tmp2 ? $ibox_tmp2.' °C' : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($ibox_ts2) }}</td>
                            <td class="small">{{ $ibox_lvl ? $ibox_lvl.'%' : '-' }}</td>

                            <!-- INST -->
                            @php
                                $pgc = $getCol(['pressure_gauge_condition']);
                                $pgsn = $getCol(['pressure_gauge_serial_number']);
                                $pgcal = $getCol(['pressure_gauge_calibration_date']);
                                
                                $p1 = $getCol(['pressure_1']);
                                $p1_ts = $getCol(['pressure_1_timestamp']);
                                
                                $p2 = $getCol(['pressure_2']);
                                $p2_ts = $getCol(['pressure_2_timestamp']);
                                
                                $lgc = $getCol(['level_gauge_condition']);
                                
                                $l1 = $getCol(['level_1']);
                                $l1_ts = $getCol(['level_1_timestamp']);
                                
                                $l2 = $getCol(['level_2']);
                                $l2_ts = $getCol(['level_2_timestamp']);
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $pgc])</td>
                            <td class="small">{{ $pgsn ?? '-' }}</td>
                            <td class="small">{{ $pgcal ? \Carbon\Carbon::parse($pgcal)->format('y-m-d') : '-' }}</td>
                            
                            <td class="small">{{ $p1 ? $p1 : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($p1_ts) }}</td>
                            
                            <td class="small">{{ $p2 ? $p2 : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($p2_ts) }}</td>
                            
                            <td>@include('admin.reports.partials.badge', ['status' => $lgc])</td>
                            
                            <td class="small">{{ $l1 ? $l1 : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($l1_ts) }}</td>
                            
                            <td class="small">{{ $l2 ? $l2 : '-' }}</td>
                            <td class="small text-muted">{{ $fmtTime($l2_ts) }}</td>

                            <!-- VAC -->
                            @php
                                $vc = $getCol(['vacuum_gauge_condition']);
                                $vpc = $getCol(['vacuum_port_suction_condition', 'port_suction_condition', 'Port Suction Condition']);
                                $vval = $getCol(['vacuum_value']);
                                $vtmp = $getCol(['vacuum_temperature']);
                                $vdt = $getCol(['vacuum_check_datetime']);
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $vc])</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $vpc])</td>
                            <td class="small">{{ $vval ? $vval.' mTorr' : '-' }}</td>
                            <td class="small">{{ $vtmp ?? '-' }}</td>
                            <td class="small">{{ $vdt ? \Carbon\Carbon::parse($vdt)->format('y-m-d') : '-' }}</td>

                            <!-- PSV -->
                            @for($i=1; $i<=4; $i++)
                                @php 
                                    $pcond = $getCol(["psv{$i}_condition"]);
                                    $psn   = $getCol(["psv{$i}_serial_number"]); 
                                    $pdt   = $getCol(["psv{$i}_calibration_date"]);
                                    // Fallback to Components if needed
                                    if (!$psn && $log->isotank) {
                                         $comp = $log->isotank->components->where('component_type', 'PSV')->where('position_code', $i)->first();
                                         if ($comp) {
                                            $psn = $comp->serial_number;
                                            if (!$pdt) $pdt = $comp->last_calibration_date;
                                         }
                                    }
                                    $dtStr = $pdt ? \Carbon\Carbon::parse($pdt)->format('y-m-d') : '-';
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $pcond])</td>
                                <td class="small">{{ $psn ?? '-' }}</td>
                                <td class="small">{{ $dtStr }}</td>
                            @endfor
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
                             <th>Cond</th><th>Bat</th><th>Prs</th>
                             <th>Tmp 1</th><th>Ts 1</th>
                             <th>Tmp 2</th><th>Ts 2</th>
                             <th>Lvl</th>

                             <th>PGC</th><th>SN</th><th>Cal</th>
                             <th>Prs 1</th><th>Ts 1</th>
                             <th>Prs 2</th><th>Ts 2</th>
                             <th>LGC</th>
                             <th>Lvl 1</th><th>Ts 1</th>
                             <th>Lvl 2</th><th>Ts 2</th>

                             <th>VC</th><th>VPC</th><th>Val</th><th>Tmp</th><th>Dt</th>
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
@push('scripts')
<script>
// Polling function to wait for Vite bundle (jQuery + Dependencies)
function waitForDependencies(callback) {
    if (window.jQuery && $.fn.DataTable && window.JSZip && window.pdfMake) {
        callback();
    } else {
        console.log('Waiting for libraries...');
        setTimeout(() => waitForDependencies(callback), 100);
    }
}

waitForDependencies(function() {
    console.log('Dependencies loaded. Initializing Table...');
    
    // Ensure DataTable is not already initialized
    if ($.fn.DataTable.isDataTable('#latestConditionTable')) {
        $('#latestConditionTable').DataTable().destroy();
    }

    // Initialize DataTable
    var table = $('#latestConditionTable').DataTable({
        dom: 'Brtip', 
        buttons: [
            { 
                extend: 'excelHtml5', 
                className: 'buttons-excel', 
                title: 'Latest_Isotank_Condition_' + new Date().toISOString().split('T')[0], 
                exportOptions: { 
                    orthogonal: 'export',
                    format: {
                        body: function ( data, row, column, node ) {
                            return data ? String(data).replace(/<[^>]+>/g, "").trim() : "";
                        }
                    }
                } 
            },
            { 
                extend: 'pdfHtml5', 
                className: 'buttons-pdf', 
                orientation: 'landscape', 
                pageSize: 'A1', 
                title: 'Latest Isotank Condition', 
                exportOptions: { 
                    orthogonal: 'export',
                    format: {
                        body: function ( data, row, column, node ) {
                            return data ? String(data).replace(/<[^>]+>/g, "").trim() : "";
                        }
                    }
                }, 
                customize: function(doc) { 
                    doc.defaultStyle.fontSize = 5; 
                    doc.styles.tableHeader.fontSize = 6;
                    // Auto-width adjustment for strict alignment
                    var colCount = doc.content[1].table.body[0].length;
                    doc.content[1].table.widths = Array(colCount).fill('*');
                } 
            }
        ],
        fixedColumns: { left: 2 },
        scrollX: true,
        ordering: false,
        pageLength: 20,
        searching: true,
        autoWidth: false
    });

    // Hide default buttons container
    $('.dt-buttons').hide();

    // Bind Custom Inputs
    $('#btnExportExcel').off('click').on('click', function() { 
        table.button('.buttons-excel').trigger(); 
    });
    
    $('#btnExportPdf').off('click').on('click', function() { 
        table.button('.buttons-pdf').trigger(); 
    });

    $('#customSearch').off('keyup change').on('keyup change', function() { 
        table.search(this.value).draw(); 
    });

    // Drag Scroll
    const slider = document.querySelector('.dataTables_scrollBody');
    if(slider) {
        let isDown = false, startX, scrollLeft;
        slider.style.cursor = 'grab';
        slider.addEventListener('mousedown', (e) => { isDown=true; slider.style.cursor='grabbing'; startX=e.pageX-slider.offsetLeft; scrollLeft=slider.scrollLeft; });
        slider.addEventListener('mouseleave', () => { isDown=false; slider.style.cursor='grab'; });
        slider.addEventListener('mouseup', () => { isDown=false; slider.style.cursor='grab'; });
        slider.addEventListener('mousemove', (e) => { if(!isDown) return; e.preventDefault(); const x=e.pageX-slider.offsetLeft; const walk=(x-startX)*2; slider.scrollLeft=scrollLeft-walk; });
    }
});
</script>
@endpush
@endsection
