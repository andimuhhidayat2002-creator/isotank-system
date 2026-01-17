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
                        <th colspan="10" class="bg-primary text-white">GENERAL CONDITION</th>
                        <th colspan="7" class="bg-success text-white">VALVE & PIPE</th>
                        <th colspan="5" class="bg-warning text-dark">IBOX</th>
                        <th colspan="6" class="bg-info text-dark">INSTRUMENTS</th>
                        <th colspan="5" class="bg-danger text-white">VACUUM</th>
                        <th colspan="12" class="bg-secondary text-white">PSV</th>
                    </tr>
                    <tr class="vertical-headers">
                        <!-- General -->
                        <th><div>Surface</div></th>
                        <th><div>Frame</div></th>
                        <th><div>Tank Plate</div></th>
                        <th><div>Venting Pipe</div></th>
                        <th><div>Expl. Cover</div></th>
                        <th><div>Grounding</div></th>
                        <th><div>Doc. Cont</div></th>
                        <th><div>Safety Label</div></th>
                        <th><div>Valve Door</div></th>
                        <th><div>Handle</div></th>
                        
                        <!-- Valve -->
                        <th><div>Valve Cond.</div></th>
                        <th><div>Position</div></th>
                        <th><div>Pipe Joint</div></th>
                        <th><div>Air Source</div></th>
                        <th><div>ESDV</div></th>
                        <th><div>Blind Flange</div></th>
                        <th><div>PRV</div></th>
                        
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
                    <tr class="text-center">
                        <td class="fw-bold text-start bg-light sticky-col">
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-decoration-none text-dark" target="_blank">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small text-muted" style="font-size:0.7em"></i>
                            </a>
                        </td>
                        <td class="small">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
                        
                        <!-- B. General -->
                        <td>@include('admin.reports.partials.badge', ['status' => $log->surface])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->frame])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->tank_plate])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->venting_pipe])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->explosion_proof_cover])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->grounding_system])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->document_container])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->safety_label])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->valve_box_door])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->valve_box_door_handle])</td>
    
                        <!-- C. Valve -->
                        <td>@include('admin.reports.partials.badge', ['status' => $log->valve_condition])</td>
                        <td><small>{{ $log->valve_position }}</small></td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->pipe_joint])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->air_source_connection])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->esdv])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->blind_flange])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->prv])</td>

                        <!-- D. IBOX -->
                        <td>@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td>
                        <td>{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.'%' : '-' }}</td>
                        <td>{{ $log->ibox_pressure ?? '-' }}</td>
                        <td>{{ $log->ibox_temperature_1 ?? ($log->ibox_temperature ?? '-') }}</td>
                        <td>{{ $log->ibox_level ?? '-' }}</td>

                        <!-- E. Instruments -->
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

                        <!-- F. Vacuum -->
                        @php
                             // Try Master Status if log is empty
                             $vacVal = $log->vacuum_value ? (float)$log->vacuum_value : null;
                             $vacTemp = $log->vacuum_temperature;
                             $vacDate = $log->vacuum_check_datetime ? \Carbon\Carbon::parse($log->vacuum_check_datetime)->format('y-m-d') : '-';
                             // If log empty, could check master table, but for now stick to log structure or fallback if needed
                        @endphp
                        <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td>
                        <td>@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition])</td>
                        <td>{{ $vacVal ?? '-' }}</td>
                        <td>{{ $vacTemp }}</td>
                        <td class="small">{{ $vacDate }}</td>

                        <!-- G. PSV -->
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
                        <th>Srf</th><th>Frm</th><th>Plt</th><th>Vnt</th><th>Exp</th><th>Grd</th><th>Doc</th><th>Lbl</th><th>Dor</th><th>Hnd</th>
                        <th>Vlv</th><th>Pos</th><th>Jnt</th><th>Air</th><th>ESD</th><th>Bld</th><th>PRV</th>
                         <th>IB.C</th><th>IB.B</th><th>IB.P</th><th>IB.T</th><th>IB.L</th>
                         <th>PG.C</th><th>PG.SN</th><th>PG.Cal</th><th>P.Val</th><th>LG.C</th><th>L.Val</th>
                         <th>VG.C</th><th>VP.C</th><th>Vac</th><th>V.tmp</th><th>Date</th>
                         <th>P1</th><th>SN</th><th>Dt</th>
                         <th>P2</th><th>SN</th><th>Dt</th>
                         <th>P3</th><th>SN</th><th>Dt</th>
                         <th>P4</th><th>SN</th><th>Dt</th>
                    </tr>
                </tfoot>
                <style>.dataTables_scrollBody { min-height: 400px; }</style>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#latestConditionTable tfoot th').each(function() {
        $(this).html('<input type="text" class="form-control form-control-sm" style="min-width: 40px;" placeholder="Filter" />');
    });

    $('#latestConditionTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm mb-3',
                title: 'Latest_Isotank_Condition'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm mb-3',
                orientation: 'landscape',
                pageSize: 'A3'
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
});
</script>
@endpush

<style>
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6 !important; }
    th { font-size: 0.65rem; text-transform: uppercase; }
    .dataTables_wrapper .dataTables_filter { text-align: left; }
    
    /* Vertical Header Styling */
    .vertical-headers th {
        height: 140px;
        vertical-align: bottom;
        padding-bottom: 15px !important;
        position: relative;
    }
    .vertical-headers th div {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: nowrap;
        margin: 0 auto;
        width: 100%;
        text-align: left; /* Becomes bottom alignment after rotation */
    }
</style>
@endsection
