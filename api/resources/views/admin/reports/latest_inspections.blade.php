@extends('layouts.app')

@section('content')
<!-- FIXED COLUMNS CSS (Only external CSS needed) -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">

<!-- Load FixedColumns JS specifically (Deferred to wait for jQuery/DT from app.js) -->
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js" defer></script>

<style>
    /* COMPACT STYLE ENFORCEMENT */
    #latestConditionTable { font-size: 0.72rem !important; }
    #latestConditionTable th, #latestConditionTable td { 
        padding: 4px 6px !important; 
        vertical-align: middle;
    }
    
    /* Layout fixes */
    .vertical-headers th { height: 140px; vertical-align: bottom; }
    .vertical-headers th div { writing-mode: vertical-rl; transform: rotate(180deg); width: 100%; }

    /* Dark Mode Sticky Fixes */
    table.dataTable tbody tr td.dtfc-fixed-left { background-color: #1a1a1a !important; color: #fff; z-index: 10; }
    table.dataTable thead tr th.dtfc-fixed-left { background-color: #333 !important; z-index: 20; }
    table.dataTable thead tr { background-color: #2d2d2d; }
    
    /* Hide Default Buttons Toolbar (We use custom triggers) */
    .dt-buttons { display: none !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
    
    <!-- MANUAL BUTTONS -->
    <div class="btn-group">
        <button type="button" class="btn btn-success" id="btnExportExcel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-danger" id="btnExportPdf">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
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
        <!-- Wrappers for FixedColumns compatibility -->
        <div class="table-responsive" style="overflow: hidden;"> 
            <table id="latestConditionTable" class="table table-striped table-bordered table-sm align-middle text-nowrap" style="width:100%">
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
                            <th colspan="{{ $items->count() }}" class="{{ $colorToggle ? 'bg-primary' : 'bg-success bg-opacity-75' }} text-white">
                                {{ $categoryMap[$catName] ?? strtoupper($catName) }}
                            </th>
                            @php $colorToggle = !$colorToggle; @endphp
                        @endforeach
                        
                        @if($category === 'all' || $category === 'T75')
                            <th colspan="5" class="bg-warning text-dark">IBOX</th>
                            <th colspan="6" class="bg-info text-white">INST</th>
                            <th colspan="5" class="bg-danger text-white">VACUUM</th>
                            <th colspan="12" class="bg-secondary text-white">PSV</th>
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
                        $iLog = $log->lastInspectionLog;
                        $logData = ($iLog && $iLog->inspection_data) ? (is_array($iLog->inspection_data) ? $iLog->inspection_data : json_decode($iLog->inspection_data, true)) : [];
                        // Logic fallback robust...
                        $legacyMap = ['Surface Condition' => 'surface', 'Tank Name Plate' => 'tank_plate', 'Valve Condition' => 'valve_condition', 'PRV' => 'prv']; 
                    @endphp
                    <tr>
                        <td class="fw-bold text-start">
                            <a href="{{ route('admin.isotanks.show', $log->isotank_id) }}" class="text-info text-decoration-none">
                                {{ $log->isotank->iso_number ?? 'UNKNOWN' }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </td>
                        <td class="small text-white">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
                         @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item)
                                @php
                                    $code = $item->code; 
                                    $val = $logData[$code] ?? null;
                                    if (!$val) $val = $iLog->$code ?? ($log->$code ?? null);
                                    if (!$val && $code === 'port_suction_condition') $val = $log->vacuum_port_suction_condition ?? null;
                                    if (!$val) $val = $logData[str_replace([' ', '.', '/'], '_', $code)] ?? null;
                                    if ($item->type === 'photo') $val = $val ? 'Uploaded' : 'Empty';
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach
                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->ibox_condition ?? 'N/A'])</td>
                            <td class="small">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.'%' : '-' }}</td>
                            <td class="small">{{ $log->ibox_pressure ?? '-' }}</td>
                            <td class="small">{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                            <td class="small">{{ $log->ibox_level ?? '-' }}</td>
                            <!-- INST -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td>
                            <td class="small">{{ $log->pressure_gauge_serial_number ?? '-' }}</td>
                            <td class="small">{{ $log->pressure_gauge_calibration_date ? \Carbon\Carbon::parse($log->pressure_gauge_calibration_date)->format('y-m-d') : '-' }}</td>
                            <td class="small">{{ $log->pressure_1 ?? '-' }}</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td>
                            <td class="small">{{ $log->level_1 ?? '-' }}</td>
                            <!-- VAC -->
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition])</td>
                            <td class="small">{{ $log->vacuum_value ? $log->vacuum_value : '-' }}</td>
                            <td class="small">{{ $log->vacuum_temperature ?? '-' }}</td>
                            <td class="small">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-' }}</td>
                            <!-- PSV -->
                            @for($i=1; $i<=4; $i++)
                                @php 
                                    $sn = $log->{"psv{$i}_serial_number"}; 
                                    $dt = $log->{"psv{$i}_calibration_date"};
                                    $dtStr = $dt ? \Carbon\Carbon::parse($dt)->format('y-m-d') : '-';
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $log->{"psv{$i}_condition"}])</td>
                                <td class="small">{{ $sn ?? '-' }}</td>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready. Waiting for Global Dependencies...');

    // Wait for window load to ensure app.js has exposed $ and DataTable
    var checkInterval = setInterval(function() {
        if (window.$ && window.DataTable) {
            clearInterval(checkInterval);
            initTable(window.$);
        }
    }, 100);

    function initTable($) {
        console.log('JQuery found. Initializing DataTable...');
        
        // Destory previous
        if ($.fn.DataTable.isDataTable('#latestConditionTable')) {
            $('#latestConditionTable').DataTable().destroy();
        }

        var table = $('#latestConditionTable').DataTable({
            // DataTables 2.0 Layout Config (The new standard)
            layout: {
                topStart: {
                    buttons: [
                        { 
                            extend: 'excelHtml5', 
                            className: 'buttons-excel',
                            title: 'Latest_Isotank_Condition',
                            exportOptions: { orthogonal: 'export' }
                        },
                        { 
                            extend: 'pdfHtml5', 
                            className: 'buttons-pdf',
                            orientation: 'landscape', 
                            pageSize: 'A2', 
                            title: 'Latest Isotank Condition',
                            exportOptions: { orthogonal: 'export' },
                            customize: function(doc) { 
                                doc.defaultStyle.fontSize = 6; 
                            }
                        }
                    ]
                }
            },
            // Legacy dom fallback (just in case)
            dom: 'Bfrtip',
            
            fixedColumns: { left: 2 },
            scrollX: true,
            ordering: false,
            pageLength: 50,
            
            initComplete: function() {
                console.log('DT Init Complete. Buttons:', this.api().buttons().count());
                
                // Footer search
                this.api().columns().every(function() {
                    var that = this;
                    var title = $(this.footer()).text();
                    $(this.footer()).html('<input type="text" class="form-control form-control-sm" placeholder="'+title+'" />');
                    $('input', this.footer()).on('keyup change clear', function() {
                        if (that.search() !== this.value) { that.search(this.value).draw(); }
                    });
                });
            }
        });

        // Trigger Logic
        $('#btnExportExcel').on('click', function() { table.button('.buttons-excel').trigger(); });
        $('#btnExportPdf').on('click', function() { table.button('.buttons-pdf').trigger(); });
        
        // Drag to scroll
        const slider = document.querySelector('.dataTables_scrollBody');
        if(slider) {
            let isDown=false, startX, scrollLeft;
            slider.style.cursor='grab';
            slider.addEventListener('mousedown', (e)=>{isDown=true; slider.style.cursor='grabbing'; startX=e.pageX-slider.offsetLeft; scrollLeft=slider.scrollLeft;});
            slider.addEventListener('mouseleave',()=>{isDown=false;slider.style.cursor='grab';});
            slider.addEventListener('mouseup',()=>{isDown=false;slider.style.cursor='grab';});
            slider.addEventListener('mousemove',(e)=>{if(!isDown)return;e.preventDefault();const x=e.pageX-slider.offsetLeft;const walk=(x-startX)*2;slider.scrollLeft=scrollLeft-walk;});
        }
    }
});
</script>
@endpush
@endsection
