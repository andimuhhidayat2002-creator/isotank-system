@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Latest Condition Master</h2>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="latestConditionTable" class="table table-bordered table-sm align-middle text-nowrap" style="font-size: 0.75rem;">
                <thead class="bg-dark text-white text-center">
                    <tr>
                        <th rowspan="2" class="align-middle bg-secondary" style="width: 120px;">ISO NUMBER</th>
                        <th rowspan="2" class="align-middle bg-secondary" style="width: 100px;">UPDATED AT</th>
                        
                        {{-- DYNAMIC SECTION B: GENERAL CONDITION --}}
                        @php
                            $genItems = $inspectionItems->filter(fn($i) => 
                                $i->category && (
                                    str_starts_with(strtolower($i->category), 'b') || 
                                    str_contains(strtolower($i->category), 'general') ||
                                    in_array(strtolower($i->category), ['external'])
                                )
                            );
                            $valveItems = $inspectionItems->filter(fn($i) => 
                                $i->category && (
                                    str_starts_with(strtolower($i->category), 'c') || 
                                    str_contains(strtolower($i->category), 'valve') ||
                                    str_contains(strtolower($i->category), 'piping')
                                )
                            );
                        @endphp

                        <th colspan="{{ $genItems->count() }}" class="bg-primary text-white">GENERAL CONDITION</th>
                        <th colspan="{{ $valveItems->count() }}" class="bg-success text-white">VALVE & PIPE</th>
                        
                        {{-- HARDCODED SECTIONS --}}
                        <th colspan="5" class="bg-warning text-dark">IBOX</th>
                        <th colspan="6" class="bg-info text-dark">INSTRUMENTS</th>
                        <th colspan="5" class="bg-danger text-white">VACUUM</th>
                        <th colspan="12" class="bg-secondary text-white">PSV</th>
                    </tr>
                    <tr class="vertical-headers">
                        {{-- DYNAMIC HEADERS --}}
                        @foreach($genItems as $item) <th><div>{{ $item->label }}</div></th> @endforeach
                        @foreach($valveItems as $item) <th><div>{{ $item->label }}</div></th> @endforeach
                        
                        {{-- HARDCODED HEADERS --}}
                        <!-- IBOX -->
                        <th><div>Condition</div></th>
                        <th><div>Battery</div></th>
                        <th><div>Pressure</div></th>
                        <th><div>Temperature</div></th>
                        <th><div>Level</div></th>
                        
                        <!-- Instruments -->
                        <th><div>PG Cond.</div></th>
                        <th><div>PG Serial</div></th>
                        <th><div>PG Calib.</div></th>
                        <th><div>Pressure</div></th>
                        <th><div>LG Cond.</div></th>
                        <th><div>Level</div></th>
                        
                        <!-- Vacuum -->
                        <th><div>VG Cond.</div></th>
                        <th><div>Port Suction</div></th>
                        <th><div>Value</div></th>
                        <th><div>Temp</div></th>
                        <th><div>Check Date</div></th>
                        
                        <!-- PSV -->
                        <th><div>PSV1 Cond</div></th><th><div>Serial</div></th><th><div>Date</div></th>
                        <th><div>PSV2 Cond</div></th><th><div>Serial</div></th><th><div>Date</div></th>
                        <th><div>PSV3 Cond</div></th><th><div>Serial</div></th><th><div>Date</div></th>
                        <th><div>PSV4 Cond</div></th><th><div>Serial</div></th><th><div>Date</div></th>
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
                        <td class="fw-bold text-start bg-light sticky-col">
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-decoration-none text-dark" target="_blank">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small text-muted"></i>
                            </a>
                        </td>
                        <td class="small">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
                        
                        {{-- DYNAMIC VALUES: GENERAL --}}
                        @foreach($genItems as $item)
                            @php 
                                $code = $item->code; 
                                $val = $logData[$code] ?? ($iLog->$code ?? ($log->$code ?? null));
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                        @endforeach

                        {{-- DYNAMIC VALUES: VALVE --}}
                        @foreach($valveItems as $item)
                             @php 
                                $code = $item->code; 
                                $val = $logData[$code] ?? ($iLog->$code ?? ($log->$code ?? null));
                            @endphp
                            <td>@include('admin.reports.partials.badge', ['status' => $val])</td>
                        @endforeach

                        {{-- HARDCODED VALUES: IBOX --}}
                         <td>@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td>
                         <td>{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.'%' : '-' }}</td>
                         <td>{{ $log->ibox_pressure ?? '-' }}</td>
                         <td>{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                         <td>{{ $log->ibox_level ?? '-' }}</td>

                        {{-- HARDCODED VALUES: INSTRUMENTS --}}
                        <td>@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td>
                         @php
                            $comps = $log->isotank->components ?? collect();
                            $pgComp = $comps->where('component_type', 'PG')->first();
                            $pgDate = $log->pressure_gauge_calibration_date 
                                ? \Carbon\Carbon::parse($log->pressure_gauge_calibration_date)->format('y-m-d')
                                : ($pgComp && $pgComp->last_calibration_date ? $pgComp->last_calibration_date->format('y-m-d') : '-');
                            $pgSerial = $log->pressure_gauge_serial_number ?: ($pgComp ? $pgComp->serial_number : '-');
                        @endphp
                        <td class="small">{{ $pgSerial }}</td>
                        <td class="small">{{ $pgDate }}</td>
                        <td>{{ $log->pressure_1 ? (float)$log->pressure_1 : '' }}</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td>
                        <td>{{ $log->level_1 ? (float)$log->level_1 : '' }}</td>

                        {{-- HARDCODED VALUES: VACUUM --}}
                        <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition])</td>
                        <td>{{ $log->vacuum_value ? (float)$log->vacuum_value : '-' }}</td>
                        <td>{{ $log->vacuum_temperature ?? '-' }}</td>
                        <td class="small">{{ $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-' }}</td>

                        {{-- HARDCODED VALUES: PSV --}}
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
                        <td class="small">{{ $p1[1] }}</td>
                        <td class="small">{{ $p1[2] }}</td>
                        
                        <td>@include('admin.reports.partials.badge', ['status' => $p2[0]])</td>
                        <td class="small">{{ $p2[1] }}</td>
                        <td class="small">{{ $p2[2] }}</td>
                        
                        <td>@include('admin.reports.partials.badge', ['status' => $p3[0]])</td>
                        <td class="small">{{ $p3[1] }}</td>
                        <td class="small">{{ $p3[2] }}</td>
                        
                        <td>@include('admin.reports.partials.badge', ['status' => $p4[0]])</td>
                        <td class="small">{{ $p4[1] }}</td>
                        <td class="small">{{ $p4[2] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th>ISO</th><th>Upd</th>
                         @foreach($genItems as $item) <th>{{ substr($item->label,0,4) }}</th> @endforeach
                         @foreach($valveItems as $item) <th>{{ substr($item->label,0,4) }}</th> @endforeach
                         
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

    $('#latestConditionTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', className: 'btn btn-success btn-sm mb-3', title: 'Latest_Isotank_Condition' },
            { extend: 'pdfHtml5', className: 'btn btn-danger btn-sm mb-3', orientation: 'landscape', pageSize: 'Legal' }
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
});
</script>
@endpush

<style>
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6 !important; }
    th { font-size: 0.65rem; text-transform: uppercase; }
    .dataTables_wrapper .dataTables_filter { text-align: left; }
    .vertical-headers th { height: 140px; vertical-align: bottom; padding-bottom: 15px !important; position: relative; }
    .vertical-headers th div { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; margin: 0 auto; width: 100%; text-align: left; }
    .sticky-col { position: sticky; left: 0; z-index: 10; background-color: #f8f9fa !important; border-right: 2px solid #dee2e6 !important; }
</style>
@endsection
