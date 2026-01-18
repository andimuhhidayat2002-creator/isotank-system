@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inspection Detail: {{ $log->isotank->iso_number }}</h2>
    <div>
        <div class="btn-group me-2">
            @if($log->pdf_path)
                <a href="{{ asset('storage/' . $log->pdf_path) }}" target="_blank" class="btn btn-outline-danger" title="View previously saved PDF">
                    <i class="bi bi-eye"></i> View Saved
                </a>
            @endif
            <a href="{{ route('admin.reports.inspection.pdf', $log->id) }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> {{ $log->pdf_path ? 'Regenerate PDF' : 'Generate PDF' }}
            </a>
        </div>
        <a href="{{ route('admin.reports.inspection') }}" class="btn btn-secondary">Back to Logs</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">A. DATA OF TANK</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Inspection Type</th><td>{{ strtoupper(str_replace('_', ' ', $log->inspection_type)) }}</td></tr>
                    <tr><th>Date</th><td>{{ $log->inspection_date->format('Y-m-d') }}</td></tr>
                    <tr><th>Inspector</th><td>{{ $log->inspector->name ?? '-' }}</td></tr>
                    <tr><th>Filling Status</th><td><b>{{ $log->filling_status_desc ?? 'Not Specified' }}</b></td></tr>
                @if($log->inspection_type === 'outgoing_inspection')
                    <tr><th>Receiver</th><td>{{ $log->receiver_name ?? 'Waiting...' }}</td></tr>
                    @if($log->receiver_confirmed_at)
                    <tr><th>Confirmed At</th><td>{{ $log->receiver_confirmed_at->format('Y-m-d H:i') }}</td></tr>
                    @endif
                @endif
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Photos (Click to Enlarge)</div>
            <div class="card-body p-2">
                <div class="row g-2">
                    @php
                        $photos = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_inside_valve_box', 'photo_additional'];
                        $hasPhotos = false;
                    @endphp
                    @foreach($photos as $p)
                        @if($log->$p)
                            @php $hasPhotos = true; @endphp
                            <div class="col-6">
                                <a href="#" onclick="showImageModal('{{ asset('storage/' . $log->$p) }}', '{{ ucfirst(str_replace(['photo_', '_'], ' ', $p)) }}'); return false;">
                                    <img src="{{ asset('storage/' . $log->$p) }}" class="img-fluid rounded border hover-shadow" alt="{{ $p }}" style="cursor: pointer; height: 120px; object-fit: cover; width: 100%;">
                                </a>
                                <small class="text-muted d-block text-center">{{ ucfirst(str_replace(['photo_', '_'], ' ', $p)) }}</small>
                            </div>
                        @endif
                    @endforeach
                    @if(!$hasPhotos)
                        <div class="col-12 text-center text-muted p-3">No Photos Available</div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($log->maintenance_notes)
        <div class="card mb-4 bg-warning bg-opacity-10 border-warning">
             <div class="card-header bg-warning text-dark">Maintenance Notes</div>
             <div class="card-body">
                 {{ $log->maintenance_notes }}
             </div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Full Inspection Checklist</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="bg-light">
                        <tr><th>Category / Item Name</th><th class="text-center" width="150">Condition/Value</th></tr>
                    </thead>
                    <tbody>
                        @php
                            // Decode JSON data once
                            $logData = [];
                            if($log->inspection_data) {
                                $logData = is_array($log->inspection_data) ? $log->inspection_data : json_decode($log->inspection_data, true);
                            }
                            
                            // Define Sections Order
                            $sections = [
                                'b' => 'B. GENERAL CONDITION',
                                'c' => 'C. VALVE & PIPE SYSTEM',
                                'd' => 'D. IBOX SYSTEM',
                                'e' => 'E. INSTRUMENTS',
                                'f' => 'F. VACUUM SYSTEM',
                                'g' => 'G. PSV (PRESSURE SAFETY VALVES)',
                                'external' => 'EXTERNAL',
                            ];
                            
                            // Map items to categories
                            // Ensure inspectionItems is available (passed from controller)
                            if(!isset($inspectionItems)) {
                                $inspectionItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order')->get();
                            }
                        @endphp

                        @foreach($sections as $catCode => $catLabel)
                            @php
                                $itemsInCat = $inspectionItems->where('category', $catCode);
                            @endphp
                            
                            @if($itemsInCat->count() > 0)
                                <tr class="table-secondary"><th colspan="2">{{ $catLabel }}</th></tr>
                                @foreach($itemsInCat as $item)
                                    @php
                                        $code = $item->code;
                                        // Priority: JSON -> Physical
                                        $val = $logData[$code] ?? ($log->$code ?? null);
                                    @endphp
                                    <tr>
                                        <td class="ps-3">{{ $item->label }}</td>
                                        <td class="text-center">
                                            @if($val)
                                                @include('admin.reports.partials.badge', ['status' => $val])
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    
                                    {{-- Magic Logic: Check for implicit extra fields (Serial, Date) often associated with items --}}
                                    @php
                                        // Try find serial number or date associated with this code
                                        // e.g. psv1_condition -> psv1_serial_number
                                        // e.g. pressure_gauge_condition -> pressure_gauge_serial_number
                                        $baseCode = str_replace('_condition', '', $code);
                                        $serialKey = $baseCode . '_serial_number';
                                        $dateKey = $baseCode . '_calibration_date';
                                        
                                        $serialVal = $log->$serialKey ?? ($logData[$serialKey] ?? null);
                                        $dateVal = $log->$dateKey ?? ($logData[$dateKey] ?? null);
                                        
                                        // Fallback for PSV naming
                                        if (!$serialVal && str_starts_with($code, 'psv') && str_ends_with($code, '_condition')) {
                                             $psvPrefix = substr($code, 0, 4); // psv1
                                             $serialVal = $log->{$psvPrefix.'_serial_number'} ?? null;
                                             $dateVal = $log->{$psvPrefix.'_calibration_date'} ?? null;
                                        }
                                    @endphp
                                    
                                    @if($serialVal || $dateVal)
                                        <tr class="bg-light">
                                            <td colspan="2" class="ps-4 small text-muted">
                                                @if($serialVal) SERIAL: <strong>{{ $serialVal }}</strong> @endif
                                                @if($dateVal) | DATE: <strong>{{ \Carbon\Carbon::parse($dateVal)->format('Y-m-d') }}</strong> @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                        
                        {{-- Handle Unmapped / Extra Items in JSON that are not in database items list --}}
                        @php
                            $mappedCodes = $inspectionItems->pluck('code')->toArray();
                            $unmapped = [];
                            foreach($logData as $k => $v) {
                                if(!in_array($k, $mappedCodes) && is_string($v) && strlen($v) < 50) {
                                     // Basic heuristic to avoid showing huge text or system fields
                                     $unmapped[$k] = $v;
                                }
                            }
                        @endphp
                        
                        @if(!empty($unmapped))
                             <tr class="table-warning"><th colspan="2">ADDITIONAL ITEMS</th></tr>
                             @foreach($unmapped as $k => $v)
                                <tr>
                                    <td class="ps-3">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                                    <td class="text-center">@include('admin.reports.partials.badge', ['status' => $v])</td>
                                </tr>
                             @endforeach
                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center bg-dark">
        <img src="" id="modalImage" class="img-fluid" style="max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function showImageModal(src, title) {
    $('#modalImage').attr('src', src);
    $('#imageModalLabel').text(title);
    $('#imageModal').modal('show');
}
</script>
<style>
    .hover-shadow { transition: transform .2s; }
    .hover-shadow:hover { transform: scale(1.05); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>
@endpush

@endsection
