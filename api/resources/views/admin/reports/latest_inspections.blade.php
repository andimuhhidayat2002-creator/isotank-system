@extends('layouts.app')

@section('content')
<!-- DataTables FixedColumns CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.bootstrap5.min.css">
<!-- FixedColumns JS -->
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/fixedColumns.bootstrap5.min.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 text-white">Latest Condition Master</h2>
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
                        @php
                            $categoryMap = [
                                'T75' => 'CRYOGENIC T75',
                                'T11' => 'CHEMICAL T11', 
                                'T50' => 'GAS T50'
                            ];
                            $colorToggle = true; 
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
                </thead>
                <tbody>
                    @foreach($logs as $row)
                    <tr>
                        <td class="fw-bold text-start">
                            <a href="{{ route('admin.isotanks.show', $row->isotank_id) }}" class="text-info text-decoration-none">
                                {{ $row->isotank->iso_number ?? 'UNKNOWN' }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </td>
                        <td class="small text-white">
                            {{ $row->inspection_date ? $row->inspection_date->format('Y-m-d') : '-' }}
                        </td>
                        
                        @foreach($groupedItems as $catName => $items)
                            @foreach($items as $item)
                                @php
                                    $slug = $item->slug;
                                    $val = $row->$slug ?? null;
                                    if ($item->type === 'photo') {
                                        $val = $val ? 'Uploaded' : 'Empty';
                                    }
                                @endphp
                                <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                            @endforeach
                        @endforeach

                        @if($category === 'all' || $category === 'T75')
                            <!-- IBOX Data -->
                            <td>@include('admin.reports.partials.badge', ['status' => $row->ibox_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $row->ibox_battery_percent ? $row->ibox_battery_percent . '%' : '-' }}</td>
                            <td class="small text-white">{{ $row->ibox_pressure ?? '-' }}</td>
                            <td class="small text-white">{{ $row->ibox_temperature ?? '-' }}</td>
                            <td class="small text-white">{{ $row->ibox_level ?? '-' }}</td>

                            <!-- INSTRUMENT Data -->
                            <td>@include('admin.reports.partials.badge', ['status' => $row->pressure_gauge_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $row->pressure_gauge_serial_number ?? '-' }}</td>
                            <td class="small text-white">{{ $row->pressure_gauge_calibration_date ? $row->pressure_gauge_calibration_date->format('y-m-d') : '-' }}</td>
                            <td class="small text-white">{{ $row->pressure_1 ?? '-' }}</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $row->level_gauge_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $row->level_1 ?? '-' }}</td>

                            <!-- VACUUM Data -->
                            <td>@include('admin.reports.partials.badge', ['status' => $row->vacuum_gauge_condition ?? 'N/A'])</td>
                            <td>@include('admin.reports.partials.badge', ['status' => $row->vacuum_port_suction_condition ?? 'N/A'])</td>
                            <td class="small text-white">{{ $row->vacuum_value ? $row->vacuum_value . ' ' . ($row->vacuum_unit ?? '') : '-' }}</td>
                            <td class="small text-white">{{ $row->vacuum_temperature ?? '-' }}</td>
                            <td class="small text-white">{{ $row->vacuum_check_datetime ? $row->vacuum_check_datetime->format('y-m-d') : '-' }}</td>

                            <!-- PSV Data -->
                            @for($i = 1; $i <= 4; $i++)
                                @php
                                    $cond = $row->{"psv{$i}_condition"} ?? 'N/A';
                                    $sn = $row->{"psv{$i}_serial_number"} ?? '-';
                                    $dt = $row->{"psv{$i}_calibration_date"};
                                    $dtStr = $dt ? $dt->format('y-m-d') : '-';
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

    try {
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
            }
        });
    } catch(e) {
        console.error("DataTables Init Error:", e);
    }

    // --- DRAG TO SCROLL (Compatible with FixedColumns) ---
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
    table.dataTable { border-collapse: collapse !important; margin-top: 0 !important; }
</style>
@endsection
