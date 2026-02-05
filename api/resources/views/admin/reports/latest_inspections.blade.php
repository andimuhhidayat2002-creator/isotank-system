@extends('layouts.app')

@section('content')
<!-- REQUIRED LIBRARIES FOR DATATABLES EXPORT & STICKY COLUMNS -->
<!-- 1. JSZip (Required for Excel Export) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<!-- 2. pdfMake (Required for PDF Export) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<!-- 3. DataTables Buttons & HTML5 Export -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<!-- 4. FixedColumns -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

<style>
    /* COMPACT TABLE STYLES */
    #latestConditionTable {
        font-size: 0.72rem !important; /* Force smaller font */
    }
    #latestConditionTable th, #latestConditionTable td {
        padding: 4px 6px !important; /* Compact padding */
    }
    .vertical-headers th { 
        height: 140px; 
        vertical-align: bottom; 
        padding-bottom: 10px !important;
    }
    .vertical-headers th div { 
        writing-mode: vertical-rl; 
        transform: rotate(180deg); 
        width: 100%; 
        text-align: left; 
    }

    /* Sticker Columns Styling */
    table.dataTable tbody tr td.dtfc-fixed-left {
        background-color: #1a1a1a !important;
        color: #fff;
        z-index: 10;
        vertical-align: middle !important;
    }
    table.dataTable thead tr th.dtfc-fixed-left {
        background-color: #333 !important;
        z-index: 20;
    }

    /* Fixed Header Overrides */
    table.dataTable thead tr { background-color: #2d2d2d; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
    
    <!-- MANUAL BUTTONS (Triggers hidden DataTable buttons) -->
    <div class="btn-group">
        <button type="button" class="btn btn-success" onclick="$('.buttons-excel').click()">
            <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
        </button>
        <button type="button" class="btn btn-danger" onclick="$('.buttons-pdf').click()">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
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
    <div class="card-body p-2"> <!-- Reduced padding -->
        <div class="table-responsive" style="overflow-x: auto; overflow-y: hidden;">
            <table id="latestConditionTable" class="table table-striped table-bordered table-sm align-middle text-nowrap" style="width:100%">
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
                                    'a' => 'A. FRONT', 'b' => 'B. REAR', 'c' => 'C. RIGHT', 'd' => 'D. LEFT', 'e' => 'E. TOP', 'other' => 'Other'
                                ];
                            } elseif ($tCat === 'T50') {
                                $categoryMap = [
                                    'a' => 'A. FRONT OUT SIDE VIEW', 'b' => 'B. REAR OUT SIDE VIEW', 'c' => 'C. RIGHT SIDE', 'd' => 'D. LEFT SIDE', 'e' => 'E. TOP', 'other' => 'Other'
                                ];
                            } else {
                                $categoryMap = [
                                    'b' => 'B. GENERAL CONDITION', 'c' => 'C. VALVES & PIPING', 'd' => 'D. IBOX SYSTEM', 'e' => 'E. INSTRUMENTS', 'f' => 'F. VACUUM SYSTEM', 'g' => 'G. SAFETY VALVES (PSV)',
                                ];
                            }
                        @endphp
                        @foreach($groupedItems as $catName => $items)
                            <th colspan="{{ $items->count() }}" class="{{ $colorToggle ? 'bg-primary' : 'bg-success bg-opacity-75' }} text-white" style="border-bottom: 2px solid white;">
                                {{ $categoryMap[$catName] ?? strtoupper($catName) }}
                            </th>
                            @php $colorToggle = !$colorToggle; @endphp
                        @endforeach
                        
                        @if($category === 'all' || $category === 'T75')
                            <th colspan="5" class="bg-warning text-dark" style="border-bottom: 2px solid white;">IBOX SYSTEM</th>
                            <th colspan="6" class="bg-info text-white" style="border-bottom: 2px solid white;">INSTRUMENT CHECK</th>
                            <th colspan="5" class="bg-danger text-white" style="border-bottom: 2px solid white;">VACUUM TEST</th>
                            <th colspan="12" class="bg-secondary text-white" style="border-bottom: 2px solid white;">PSV CHECK</th>
                        @endif
                    </tr>
                    <tr>
                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item) 
                                @php $displayLabel = str_replace(['FRONT: ', 'REAR: ', 'RIGHT: ', 'LEFT: ', 'TOP: '], '', $item->label); @endphp
                                <th class="vertical-headers"><div>{{ substr($displayLabel,0,25) }}</div></th> 
                            @endforeach
                        @endforeach

                        @if($category === 'all' || $category === 'T75')
                             <th>Cond</th><th>Bat</th><th>Prs</th><th>Tmp</th><th>Lvl</th>
                             <th>PGC</th><th>SN</th><th>Cal</th><th>Prs</th><th>LGC</th><th>Lvl</th>
                             <th>VC</th><th>VPC</th><th>Val</th><th>Tmp</th><th>Dt</th>
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
                        // DATA PREPARATION LOGIC (PRESERVED)
                        $iLog = $log->lastInspectionLog;
                        $logData = ($iLog && $iLog->inspection_data) 
                             ? (is_array($iLog->inspection_data) ? $iLog->inspection_data : json_decode($iLog->inspection_data, true))
                             : [];

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
                    @endphp
                    <tr>
                        <td class="fw-bold text-start">
                            @if($log->isotank)
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-info text-decoration-none">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                            @else
                                <span class="text-muted">UNKNOWN</span>
                            @endif
                        </td>
                        <td class="small text-white py-1">
                            {{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}
                        </td>
                        
                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item)
                                @php
                                    $code = $item->code; 
                                    $label = $item->label;
                                    $val = $logData[$code] ?? null;
                                    if (!$val) $val = $iLog->$code ?? ($log->$code ?? null);
                                    if (!$val && $code === 'port_suction_condition') $val = $log->vacuum_port_suction_condition ?? null;
                                    if (!$val) $val = $logData[str_replace([' ', '.', '/'], '_', $code)] ?? null;
                                    if (!$val && isset($legacyMap[$label])) {
                                        $lKey = $legacyMap[$label];
                                        $val = $logData[$lKey] ?? ($iLog->$lKey ?? ($log->$lKey ?? null));
                                    }
                                    if (!$val) $val = $logData[str_replace([' ', '.', '/'], '_', strtolower($label))] ?? null;
                                    if ($item->type === 'photo') $val = $val ? 'Uploaded' : 'Empty';
                                @endphp
                                <td class="py-1">@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach

                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX Data -->
                            <td class="py-1">@include('admin.reports.partials.badge', ['status' => $log->ibox_condition ?? 'N/A'])</td>
                            <td class="small text-white py-1">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent . '%' : '-' }}</td>
                            <td class="small text-white py-1">{{ $log->ibox_pressure ?? '-' }}</td>
                            <td class="small text-white py-1">{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                            <td class="small text-white py-1">{{ $log->ibox_level ?? '-' }}</td>

                            <!-- INSTRUMENTS -->
                            <td class="py-1">@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition ?? 'N/A'])</td>
                            @php
                                $comps = $log->isotank->components ?? collect();
                                $pgComp = $comps->where('component_type', 'PG')->first();
                                $pgDate = $log->pressure_gauge_calibration_date 
                                    ? \Carbon\Carbon::parse($log->pressure_gauge_calibration_date)->format('y-m-d') 
                                    : ($pgComp && $pgComp->last_calibration_date ? $pgComp->last_calibration_date->format('y-m-d') : '-');
                                $pgSerial = $log->pressure_gauge_serial_number ?: ($pgComp ? $pgComp->serial_number : '-');
                            @endphp
                            <td class="small text-white py-1">{{ $pgSerial }}</td>
                            <td class="small text-white py-1">{{ $pgDate }}</td>
                            <td class="small text-white py-1">{{ $log->pressure_1 ?? '-' }}</td>
                            <td class="py-1">@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition ?? 'N/A'])</td>
                            <td class="small text-white py-1">{{ $log->level_1 ?? '-' }}</td>

                            <!-- VACUUM -->
                            <td class="py-1">@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition ?? 'N/A'])</td>
                            <td class="py-1">@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition ?? 'N/A'])</td>
                            <td class="small text-white py-1">{{ $log->vacuum_value ? $log->vacuum_value . ' ' . ($log->vacuum_unit ?? '') : '-' }}</td>
                            <td class="small text-white py-1">{{ $log->vacuum_temperature ?? '-' }}</td>
                            <td class="small text-white py-1">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-' }}</td>

                            <!-- PSV -->
                            @for($i = 1; $i <= 4; $i++)
                                @php
                                    $cond = $log->{"psv{$i}_condition"} ?? 'N/A';
                                    $sn = $log->{"psv{$i}_serial_number"};
                                    $dt = $log->{"psv{$i}_calibration_date"};
                                    if (!$sn || !$dt) {
                                        $pComp = $comps->where('component_type', 'PSV')->where('position_code', $i)->first();
                                        if (!$sn) $sn = $pComp->serial_number ?? '-';
                                        if (!$dt && $pComp && $pComp->last_calibration_date) $dt = $pComp->last_calibration_date;
                                    }
                                    $dtStr = $dt ? (\Carbon\Carbon::parse($dt)->format('y-m-d')) : '-';
                                @endphp
                                <td class="py-1">@include('admin.reports.partials.badge', ['status' => $cond])</td>
                                <td class="small text-white py-1">{{ $sn }}</td>
                                <td class="small text-white py-1">{{ $dtStr }}</td>
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
                             <th>Cond</th><th>Bat</th><th>Prs</th><th>Tmp</th><th>Lvl</th>
                             <th>PGC</th><th>SN</th><th>Cal</th><th>Prs</th><th>LGC</th><th>Lvl</th>
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
<script>
$(document).ready(function() {
    // Inject Footer Inputs
    $('#latestConditionTable tfoot th').each(function() {
        var title = $(this).text();
        $(this).html('<input type="text" class="form-control form-control-sm" style="min-width: 40px; font-size: 0.7rem;" placeholder="'+title+'" />');
    });

    var table = $('#latestConditionTable').DataTable({
        // Minimal DOM to hide default buttons (we trigger them externally)
        dom: "<'row mb-2'<'col-md-6'l><'col-md-6'f>>" +
             "<'row'<'col-12'tr>>" + 
             "<'row'<'col-md-5'i><'col-md-7'p>>",
        
        fixedColumns: {
            left: 2
        },
        scrollX: true,
        ordering: false, // Disable ordering to boost init speed
        
        // Define buttons but don't show them in DOM (we use custom buttons to trigger them)
        buttons: [
            { 
                extend: 'excelHtml5', 
                className: 'buttons-excel d-none', // Hidden class
                title: 'Latest_Isotank_Condition',
                exportOptions: { 
                    orthogonal: 'export',
                    format: {
                        header: function ( data, columnIdx ) {
                            return $(data).text() || data; // Clean vertical headers
                        }
                    }
                }
            },
            { 
                extend: 'pdfHtml5', 
                className: 'buttons-pdf d-none', // Hidden class
                orientation: 'landscape', 
                pageSize: 'A2', // Larger Page for Huge Table
                title: 'Latest Isotank Condition',
                exportOptions: { 
                    orthogonal: 'export',
                     format: {
                        header: function ( data, columnIdx ) {
                            return $(data).text() || data;
                        }
                    }
                },
                customize: function(doc) {
                    doc.defaultStyle.fontSize = 6;
                    doc.styles.tableHeader.fontSize = 7;
                }
            }
        ],
        pageLength: 50,
        initComplete: function() {
            // Apply footer search
            this.api().columns().every(function() {
                var that = this;
                $('input', this.footer()).on('keyup change clear', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });
            });
            console.log('DT Initialized. JSZip loaded:', typeof JSZip !== 'undefined');
        }
    });
    
    // Explicitly initialize buttons and append to a hidden container to ensure they are active
    table.buttons().container().appendTo('#d-none-container');

    // --- DRAG TO SCROLL ---
    const slider = document.querySelector('.dataTables_scrollBody');
    if(slider) {
        let isDown = false;
        let startX;
        let scrollLeft;
        slider.style.cursor = 'grab';
        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.style.cursor = 'grabbing';
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', () => { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mouseup', () => { isDown = false; slider.style.cursor = 'grab'; });
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            slider.scrollLeft = scrollLeft - walk;
        });
    }
});
</script>
<div id="d-none-container" style="display:none;"></div>
@endpush
@endsection
