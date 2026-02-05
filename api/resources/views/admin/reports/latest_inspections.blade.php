@extends('layouts.app')

@section('content')
<!-- DataTables FixedColumns CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.bootstrap5.min.css">
<!-- FixedColumns JS -->
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/fixedColumns.bootstrap5.min.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
    <!-- Buttons handled by DataTables Native DOM -->
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
                                    'a' => 'A. FRONT', 'b' => 'B. REAR', 'c' => 'C. RIGHT', 'd' => 'D. LEFT', 'e' => 'E. TOP', 'other' => 'Other / Internal'
                                ];
                            } elseif ($tCat === 'T50') {
                                $categoryMap = [
                                    'a' => 'A. FRONT OUT SIDE VIEW', 'b' => 'B. REAR OUT SIDE VIEW', 'c' => 'C. RIGHT SIDE/VALVE BOX OBSERVATION', 'd' => 'D. LEFT SIDE', 'e' => 'E. TOP', 'other' => 'Other / Internal'
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
                        // DATA PREPARATION LOGIC (COPIED FROM WORKING VERSION to ensure data visibility)
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
                            {{-- Check Relation Safety --}}
                            @if($log->isotank)
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-info text-decoration-none">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                            @else
                                <span class="text-muted">UNKNOWN</span>
                            @endif
                        </td>
                        <td class="small text-white">
                            {{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}
                        </td>
                        
                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item)
                                @php
                                    $code = $item->code; 
                                    $label = $item->label;
                                    
                                    // ROBUST LOOKUP (Preserving existing logic)
                                    $val = $logData[$code] ?? null;
                                    
                                    // Direct Column match in MasterLatestInspection (Prioritize this if available)
                                    // Note: MasterLatestInspection attributes are flat (e.g. $log->surface)
                                    // But $item->code might be 'surface_condition' while column is 'surface'.
                                    // We check logic below.
                                    
                                    if (!$val) $val = $iLog->$code ?? ($log->$code ?? null);
                                    
                                    if (!$val && $code === 'port_suction_condition') {
                                        $val = $log->vacuum_port_suction_condition ?? null;
                                    }
                                    
                                    if (!$val) {
                                        $uCode = str_replace([' ', '.', '/'], '_', $code);
                                        $val = $logData[$uCode] ?? null;
                                    }
                                    
                                    if (!$val && isset($legacyMap[$label])) {
                                        $lKey = $legacyMap[$label];
                                        $val = $logData[$lKey] ?? ($iLog->$lKey ?? ($log->$lKey ?? null));
                                    }
                                    
                                    if (!$val) {
                                        $uLabel = str_replace([' ', '.', '/'], '_', strtolower($label));
                                        $val = $logData[$uLabel] ?? null;
                                    }

                                    if ($item->type === 'photo') {
                                        $val = $val ? 'Uploaded' : 'Empty';
                                    }
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach

                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX Data -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->ibox_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent . '%' : '-' }}</td>
                            <td class="small text-white">{{ $log->ibox_pressure ?? '-' }}</td>
                            <td class="small text-white">{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                            <td class="small text-white">{{ $log->ibox_level ?? '-' }}</td>

                            <!-- INSTRUMENTS -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition ?? 'N/A'])</td>
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
                            <td class="small text-white">{{ $log->pressure_1 ?? '-' }}</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $log->level_1 ?? '-' }}</td>

                            <!-- VACUUM -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition ?? 'N/A'])</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $log->vacuum_value ? $log->vacuum_value . ' ' . ($log->vacuum_unit ?? '') : '-' }}</td>
                            <td class="small text-white">{{ $log->vacuum_temperature ?? '-' }}</td>
                            <td class="small text-white">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-' }}</td>

                            <!-- PSV -->
                            @for($i = 1; $i <= 4; $i++)
                                @php
                                    $cond = $log->{"psv{$i}_condition"} ?? 'N/A';
                                    $sn = $log->{"psv{$i}_serial_number"};
                                    $dt = $log->{"psv{$i}_calibration_date"};
                                    
                                    // Fallback to components if log is empty
                                    if (!$sn || !$dt) {
                                        $pComp = $comps->where('component_type', 'PSV')->where('position_code', $i)->first();
                                        if (!$sn) $sn = $pComp->serial_number ?? '-';
                                        if (!$dt && $pComp && $pComp->last_calibration_date) {
                                            $dt = $pComp->last_calibration_date;
                                        }
                                    }
                                    
                                    $dtStr = $dt ? (\Carbon\Carbon::parse($dt)->format('y-m-d')) : '-';
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $cond])</td>
                                <td class="small text-white">{{ $sn }}</td>
                                <td class="small text-white">{{ $dtStr }}</td>
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
        $(this).html('<input type="text" class="form-control form-control-sm" style="min-width: 40px;" placeholder="'+title+'" />');
    });

    var table = $('#latestConditionTable').DataTable({
        // DOM: l=length, B=buttons, f=filter, r=processing, t=table, i=info, p=pagination
        dom: "<'row mb-3'<'col-md-4'l><'col-md-8 text-end'Bf>>" +
             "<'row'<'col-12'tr>>" + 
             "<'row'<'col-md-5'i><'col-md-7'p>>",
        
        fixedColumns: {
            left: 2
        },
        scrollX: true,
        
        buttons: [
            { 
                extend: 'excelHtml5', 
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm me-2',
                title: 'Latest_Isotank_Condition',
                exportOptions: { orthogonal: 'export' }
            },
            { 
                extend: 'pdfHtml5', 
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape', 
                pageSize: 'A3',
                title: 'Latest Isotank Condition',
                exportOptions: { orthogonal: 'export' }
            }
        ],
        pageLength: 50,
        order: [[0, 'asc']],
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
            console.log('DT Initialized with FixedColumns & Built-in Buttons');
        }
    });

    // --- DRAG TO SCROLL (Compatible with FixedColumns) ---
    // Note: FixedColumns creates complexity with drag-scroll on the whole table
    // We target dataTables_scrollBody which is the scrollable container
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

        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });

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
@endpush

<style>
    /* Styling Correction for DataTables FixedColumns */
    table.dataTable thead tr { background-color: #2d2d2d; }
    
    /* Fixed Column Backgrounds - Dark Theme */
    table.dataTable tbody tr td.dtfc-fixed-left {
        background-color: #1a1a1a !important;
        border-right: 1px solid #444 !important;
        color: #fff;
        z-index: 10;
        /* Correction for sticky override */
        position: sticky !important; 
    }
    
    table.dataTable thead tr th.dtfc-fixed-left {
        background-color: #2d2d2d !important;
        z-index: 20;
    }
    
    .dtfc-fixed-left { box-shadow: 2px 0 5px rgba(0,0,0,0.5); }

    .vertical-headers th { height: 140px; vertical-align: bottom; }
    .vertical-headers th div { writing-mode: vertical-rl; transform: rotate(180deg); width: 100%; text-align: left; }
    
    .dt-buttons .btn { margin-right: 5px; }
    
    /* Force collapse for alignment */
    table.dataTable { border-collapse: collapse !important; border-spacing: 0 !important; }
</style>
@endsection
