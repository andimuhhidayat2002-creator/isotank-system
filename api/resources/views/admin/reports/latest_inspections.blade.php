@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Latest Condition Master</h2>
</div>

@php
    // Mapping Category Codes to Readable Labels & Colors
    // Keys should match the 'category' column in inspection_items
    $catMap = [
        'b' => ['label' => 'GENERAL CONDITION', 'class' => 'bg-primary'],
        'c' => ['label' => 'VALVE & PIPE SYSTEM', 'class' => 'bg-success'],
        'd' => ['label' => 'IBOX SYSTEM', 'class' => 'bg-warning text-dark'],
        'e' => ['label' => 'INSTRUMENT', 'class' => 'bg-info text-dark'],
        'f' => ['label' => 'VACUUM', 'class' => 'bg-danger'],
        'g' => ['label' => 'PSV', 'class' => 'bg-secondary'],
        'external' => ['label' => 'EXTERNAL', 'class' => 'bg-primary'],
        'general' => ['label' => 'GENERAL', 'class' => 'bg-primary'],
        'valve' => ['label' => 'VALVE', 'class' => 'bg-success'],
        'internal' => ['label' => 'INTERNAL', 'class' => 'bg-secondary'],
        'safety' => ['label' => 'SAFETY', 'class' => 'bg-danger'],
    ];
@endphp

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="latestConditionTable" class="table table-bordered table-sm align-middle text-nowrap" style="font-size: 0.75rem;">
                <thead class="bg-dark text-white text-center">
                    <tr>
                        <th rowspan="2" class="align-middle bg-secondary" style="width: 120px;">ISO NUMBER</th>
                        <th rowspan="2" class="align-middle bg-secondary" style="width: 100px;">UPDATED AT</th>
                        
                        {{-- Loop Categories Groups for Main Headers --}}
                        @if(isset($groupedItems) && $groupedItems->count() > 0)
                            @foreach($groupedItems as $cat => $items)
                                @php 
                                    $catCode = strtolower($cat ?? 'other');
                                    // Use mapped label or fallback to category name
                                    $info = $catMap[$catCode] ?? ['label' => strtoupper($cat ?? 'OTHER'), 'class' => 'bg-secondary'];
                                @endphp
                                <th colspan="{{ $items->count() }}" class="{{ $info['class'] }} text-white">{{ $info['label'] }}</th>
                            @endforeach
                        @else
                           <th class="bg-danger text-white">NO ITEMS DEFINED</th>
                        @endif
                    </tr>
                    <tr class="vertical-headers">
                        {{-- Loop All Items for Sub Headers --}}
                         @if(isset($groupedItems))
                            @foreach($groupedItems as $cat => $items)
                                @foreach($items as $item)
                                    <th><div>{{ $item->label }}</div></th>
                                @endforeach
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php 
                        $iLog = $log->lastInspectionLog; 
                        // Cached JSON data array if available
                        $logData = ($iLog && $iLog->inspection_data) 
                            ? (is_array($iLog->inspection_data) ? $iLog->inspection_data : json_decode($iLog->inspection_data, true))
                            : [];
                    @endphp
                    <tr class="text-center">
                         <td class="fw-bold text-start bg-light sticky-col">
                            <a href="{{ route('admin.isotanks.show', $log->isotank->id) }}" class="text-decoration-none text-dark" target="_blank">
                                {{ $log->isotank->iso_number }} <i class="bi bi-box-arrow-up-right small text-muted" style="font-size:0.7em"></i>
                            </a>
                        </td>
                        <td class="small">{{ $log->updated_at ? $log->updated_at->format('Y-m-d') : '-' }}</td>
                        
                         @if(isset($groupedItems))
                            @foreach($groupedItems as $cat => $items)
                                @foreach($items as $item)
                                    @php
                                        $code = $item->code;
                                        $val = null;
                                        
                                        // Priority 1: Check JSON data from the actual log
                                        if (isset($logData[$code])) {
                                            $val = $logData[$code];
                                        }
                                        
                                        // Priority 2: Check physical column in InspectionLog (legacy)
                                        if (!$val && $iLog && isset($iLog->$code)) {
                                            $val = $iLog->$code;
                                        }
                                        
                                        // Priority 3: Fallback to Master table physical column
                                        if (!$val && isset($log->$code)) {
                                            $val = $log->$code;
                                        }

                                        // Normalize value
                                        $displayVal = $val;
                                        $isBadge = false;
                                        
                                        if ($val) {
                                            $lowerVal = strtolower($val);
                                            if (in_array($lowerVal, ['good', 'not_good', 'need_attention', 'fair', 'poor', 'na', 'yes', 'no', 'correct', 'incorrect', 'valid', 'expired', 'active', 'inactive'])) {
                                                $isBadge = true;
                                            } elseif (str_ends_with($code, '_date') || str_contains($code, 'date')) {
                                                 // Try format date
                                                 try {
                                                    $displayVal = \Carbon\Carbon::parse($val)->format('y-m-d');
                                                 } catch(\Exception $e) {}
                                            }
                                        }
                                    @endphp
                                    <td>
                                        @if($isBadge)
                                            @include('admin.reports.partials.badge', ['status' => $val])
                                        @else
                                            {{ $displayVal ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                 <tfoot class="bg-light">
                    <tr>
                        <th>ISO</th><th>Upd</th>
                         @if(isset($groupedItems))
                            @foreach($groupedItems as $cat => $items)
                                @foreach($items as $item)
                                    <th>{{ Illuminate\Support\Str::limit($item->label, 3) }}</th>
                                @endforeach
                            @endforeach
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
    // Initialize Footer Filters
    $('#latestConditionTable tfoot th').each(function() {
        $(this).html('<input type="text" class="form-control form-control-sm" style="min-width: 40px;" placeholder="" />');
    });

    // Initialize DataTable
    var table = $('#latestConditionTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm mb-3',
                title: 'Latest_Isotank_Condition_Master'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm mb-3',
                orientation: 'landscape',
                pageSize: 'LEGAL', // Use LEGAL or A3 for wide tables
                title: 'Details'
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
        text-align: left; 
    }
    
    /* Sticky First Column */
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: #f8f9fa !important;
        border-right: 2px solid #dee2e6 !important;
    }
</style>
@endsection
