@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Vacuum Management</h2>
</div>

<ul class="nav nav-tabs mb-3" id="vacuumTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab" aria-controls="monitoring" aria-selected="true">
            <i class="bi bi-activity"></i> Monitoring Process (5 Days)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
            <i class="bi bi-clock-history"></i> All Vacuum History
        </button>
    </li>
</ul>

<div class="tab-content" id="vacuumTabContent">
    <!-- TAB 1: Monitoring Process -->
    <div class="tab-pane fade show active" id="monitoring" role="tabpanel" aria-labelledby="monitoring-tab">
        <div class="card shadow-sm border-top-0 rounded-0 rounded-bottom">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="vacuumTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark text-center" style="font-size: 0.8rem;">
                            <tr>
                                <th class="ps-3 text-start">ISO NUMBER</th>
                                <th style="width: 15%;">DAY 1 (INITIAL)</th>
                                <th style="width: 15%;">DAY 2</th>
                                <th style="width: 15%;">DAY 3</th>
                                <th style="width: 15%;">DAY 4</th>
                                <th style="width: 15%;">DAY 5</th>
                                <th class="pe-3">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $session)
                            <tr>
                                <td class="ps-3 fw-bold text-primary">
                                    {{ $session['isotank']->iso_number }}
                                    <div class="text-muted x-small fw-normal">Start: {{ $session['start_date']->format('Y-m-d') }}</div>
                                </td>
                                
                                {{-- Day 1 Column --}}
                                <td class="p-2 border-start">
                                    <div class="text-center">
                                        <div class="badge bg-light text-dark border mb-2 w-100" style="font-size: 0.7rem;">
                                            {{ isset($session['days'][1]) ? $session['days'][1]->created_at->format('d M Y') : $session['start_date']->format('d M Y') }}
                                        </div>
                                        <div class="row gx-1 text-start" style="font-size: 0.7rem;">
                                            <div class="col-7 text-muted">Port Vac:</div>
                                            <div class="col-5 fw-bold text-end">{{ isset($session['day1_summary']['portable_vacuum']) ? (float)$session['day1_summary']['portable_vacuum'] : '-' }}</div>
                                            <div class="col-7 text-muted">Mch Stop:</div>
                                            <div class="col-5 fw-bold text-success text-end">{{ isset($session['day1_summary']['mch_stop']) ? (float)$session['day1_summary']['mch_stop'] : '-' }}</div>
                                            @if($session['day1_summary']['temp'])
                                                <div class="col-7 text-muted">Temp:</div>
                                                <div class="col-5 text-end">{{ (float)$session['day1_summary']['temp'] }}°C</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                {{-- Day 2-5 --}}
                                @for($d=2; $d<=5; $d++)
                                    <td class="p-2 border-start">
                                        @if(isset($session['days'][$d]))
                                            @php $day = $session['days'][$d]; @endphp
                                            <div class="text-center">
                                                <div class="badge bg-light text-dark border mb-2 w-100" style="font-size: 0.7rem;">
                                                    {{ $day->created_at->format('d M Y') }}
                                                </div>
                                                <div class="text-start" style="font-size: 0.7rem;">
                                                    {{-- AM --}}
                                                    <div class="border-bottom pb-1 mb-1">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">AM:</span>
                                                            <span class="fw-bold">{{ isset($day->morning_vacuum_value) ? (float)$day->morning_vacuum_value : '-' }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.65rem;">
                                                            <span>Temp:</span>
                                                            <span>{{ $day->morning_temperature ? (float)$day->morning_temperature . '°C' : '-' }}</span>
                                                        </div>
                                                        @if($day->morning_timestamp)
                                                        <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.65rem;">
                                                            <span>Time:</span>
                                                            <span>{{ \Carbon\Carbon::parse($day->morning_timestamp)->format('H:i') }}</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    {{-- PM --}}
                                                    <div>
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted">PM:</span>
                                                            <span class="fw-bold">{{ isset($day->evening_vacuum_value) ? (float)$day->evening_vacuum_value : '-' }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.65rem;">
                                                            <span>Temp:</span>
                                                            <span>{{ $day->evening_temperature ? (float)$day->evening_temperature . '°C' : '-' }}</span>
                                                        </div>
                                                        @if($day->evening_timestamp)
                                                        <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.65rem;">
                                                            <span>Time:</span>
                                                            <span>{{ \Carbon\Carbon::parse($day->evening_timestamp)->format('H:i') }}</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <span class="text-muted opacity-25" style="font-size: 1.2rem;">-</span>
                                            </div>
                                        @endif
                                    </td>
                                @endfor
                                
                                <td class="text-center pe-3">
                                    @if($session['is_completed'])
                                        <span class="badge bg-success px-3">COMPLETED</span>
                                    @else
                                        <span class="badge bg-warning text-dark px-3">IN PROGRESS</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>ISO Number</th>
                                <th>Day 1</th>
                                <th>Day 2</th>
                                <th>Day 3</th>
                                <th>Day 4</th>
                                <th>Day 5</th>
                                <th>Status</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Vacuum History -->
    <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
        <div class="card shadow-sm border-top-0 rounded-0 rounded-bottom">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="historyTable" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Check Date</th>
                                <th>ISO Number</th>
                                <th>Vacuum Value</th>
                                <th>Temperature</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vacuumLogs as $log)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($log->check_datetime)->format('Y-m-d H:i') }}</td>
                                <td class="fw-bold text-primary">{{ $log->isotank->iso_number ?? '-' }}</td>
                                <td>
                                    <span class="fw-bold">{{ (float)$log->vacuum_value_mtorr }}</span> mTorr
                                    @if($log->vacuum_unit_raw && $log->vacuum_unit_raw !== 'mtorr')
                                        <small class="text-muted ms-1">({{ $log->vacuum_value_raw }} {{ $log->vacuum_unit_raw }})</small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->temperature)
                                        {{ $log->temperature }}°C
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->source == 'inspection')
                                        <span class="badge bg-info text-dark">Inspection</span>
                                    @elseif($log->source == 'suction')
                                        <span class="badge bg-warning text-dark">Suction Process</span>
                                    @else
                                        <span class="badge bg-secondary">Monitoring</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Date</th>
                                <th>ISO Number</th>
                                <th>Value</th>
                                <th>Temp</th>
                                <th>Source</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // ---- VACUUM MONITORING TABLE CONFIG ----
    $('#vacuumTable tfoot th').each(function() {
        var title = $(this).text();
        if (title == 'ISO Number' || title == 'Status') {
            $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter ' + title + '" />');
        } else {
            $(this).html('');
        }
    });

    $('#vacuumTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Monitoring',
                className: 'btn btn-success btn-sm mb-3',
                title: 'Vacuum_Monitoring_Process',
                customize: function(xlsx) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c', sheet).attr('s', '55'); // Center align and wrap text
                },
                exportOptions: {
                    format: {
                        body: function ( data, row, column, node ) {
                            // Helper to clean text
                            const clean = (text) => text ? text.replace(/\s+/g, ' ').trim() : '';

                            // 1. ISO Number Column
                            if (column === 0) {
                                let iso = $(node).contents().filter(function() { return this.nodeType == 3; }).text().trim();
                                let date = $(node).find('.text-muted').text().trim();
                                return iso + "\n" + date;
                            }

                            // 2. Status Column (Last one)
                            if (column === 6) {
                                return $(node).text().trim();
                            }

                            // 3. Data Columns (Days 1-5)
                            let text = "";
                            
                            // Get Date Header
                            let dateLabel = $(node).find('.badge').text().trim();
                            if(dateLabel) text += dateLabel + "\n";

                            // Day 1 Specifics
                            if (column === 1) {
                                $(node).find('.row .col-7').each(function(index) {
                                    let label = $(this).text().replace(':', '').trim();
                                    let value = $(this).next('.col-5').text().trim();
                                    if(label) text += label + ": " + value + "\n";
                                });
                            } 
                            // Days 2-5 Specifics
                            else {
                                // AM Block
                                let amBlock = $(node).find('.border-bottom');
                                if (amBlock.length) {
                                    let vacVal = amBlock.find('.fw-bold').text().trim() || '-';
                                    text += "AM Vac: " + vacVal + "\n";
                                    
                                    amBlock.find('.d-flex.x-small').each(function() {
                                        let parts = $(this).find('span');
                                        if(parts.length >= 2) {
                                            text += $(parts[0]).text().replace(':', '') + ": " + $(parts[1]).text() + "\n";
                                        }
                                    });
                                }

                                // PM Block (The div after AM block)
                                let pmBlock = $(node).find('.text-start > div:last-child');
                                if (pmBlock.length && !pmBlock.hasClass('border-bottom')) {
                                    let vacVal = pmBlock.find('.fw-bold').text().trim() || '-';
                                    // ensure we don't duplicate if there is only AM or something weird
                                    if(vacVal) {
                                        text += "\nPM Vac: " + vacVal + "\n";
                                        pmBlock.find('.d-flex.x-small').each(function() {
                                            let parts = $(this).find('span');
                                            if(parts.length >= 2) {
                                                text += $(parts[0]).text().replace(':', '') + ": " + $(parts[1]).text() + "\n";
                                            }
                                        });
                                    }
                                }
                            }

                            // If empty (e.g. empty cell), return cleaned data
                            return text.trim() || clean($(node).text());
                        }
                    }
                }
            }
        ],
        pageLength: 25,
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

    // ---- VACUUM HISTORY TABLE CONFIG ----
    $('#historyTable tfoot th').each(function() {
        var title = $(this).text();
        $(this).html('<input type="text" class="form-control form-control-sm" placeholder="Filter" />');
    });

    $('#historyTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export History',
                className: 'btn btn-outline-success btn-sm mb-3',
                title: 'Vacuum_Logs_History'
            }
        ],
        pageLength: 25,
        order: [[0, 'desc']], // Sort by Date Descending
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
    .x-small { font-size: 0.7rem; }
    .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.02); }
    th { letter-spacing: 0.05rem; }
    /* Fix datatables input width in footer */
    tfoot input.form-control { width: 100%; }
</style>
@endsection
