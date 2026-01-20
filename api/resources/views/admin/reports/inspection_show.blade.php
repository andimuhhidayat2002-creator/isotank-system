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
                            // Load Dynamic Items if not present
                            if(!isset($inspectionItems)) {
                                $inspectionItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order')->get();
                            }
                            
                            $logData = is_array($log->inspection_data) ? $log->inspection_data : json_decode($log->inspection_data, true) ?? [];
                            
                            // CALCULATE UNMAPPED ITEMS HERE
                            $standardCodes = $inspectionItems->pluck('code')->toArray();
                            $unmapped = [];
                            foreach($logData as $k => $v) {
                                // Exclude standard codes (already shown in B & C) and known system fields
                                if(!in_array($k, $standardCodes) && 
                                   !in_array($k, ['inspection_date', 'inspector_name', 'filling_status', 'remarks', 'signature', 'longitude', 'latitude', 'location_name']) &&
                                   is_string($v) && strlen($v) < 50) {
                                     // Also exclude hardcoded legacy fields if they appear in JSON
                                     if(!str_contains($k, 'ibox') && !str_contains($k, 'vacuum') && !str_contains($k, 'pressure_gauge') && !str_contains($k, 'psv')) {
                                         $unmapped[$k] = $v;
                                     }
                                }
                            }
                        @endphp

                        <!-- SECTION B: GENERAL CONDITION (Dynamic + Hybrid + Unmapped) -->
                        <tr class="table-secondary"><th colspan="2">B. GENERAL CONDITION</th></tr>
                        @php
                            // Filter items that are 'b', 'general', or 'external'
                             $generalItems = $inspectionItems->filter(fn($i) => 
                                $i->category && (
                                    str_starts_with(strtolower($i->category), 'b') || 
                                    str_contains(strtolower($i->category), 'general') ||
                                    in_array(strtolower($i->category), ['external'])
                                )
                            );
                        @endphp
                        
                        @foreach($generalItems as $item)
                             @php $code = $item->code; $val = $logData[$code] ?? ($log->$code ?? null); @endphp
                             <tr>
                                <td class="ps-3">{{ $item->label }}</td>
                                <td class="text-center">
                                    @include('admin.reports.partials.badge', ['status' => $val ?: '-'])
                                </td>
                             </tr>
                        @endforeach
                        
                        {{-- RENDER UNMAPPED / EXTRA ITEMS HERE INSIDE SECTION B --}}
                        @if(!empty($unmapped))
                             @foreach($unmapped as $k => $v)
                                <tr>
                                    <td class="ps-3">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                                    <td class="text-center">@include('admin.reports.partials.badge', ['status' => $v])</td>
                                </tr>
                             @endforeach
                        @endif

                        <!-- SECTION C: VALVE & PIPE SYSTEM (Dynamic + Hybrid) -->
                        <tr class="table-secondary"><th colspan="2">C. VALVE & PIPE SYSTEM</th></tr>
                        @php
                            $valveItems = $inspectionItems->filter(fn($i) => 
                                $i->category && (
                                    str_starts_with(strtolower($i->category), 'c') || 
                                    str_contains(strtolower($i->category), 'valve') ||
                                    str_contains(strtolower($i->category), 'piping')
                                )
                            );
                        @endphp
                         @foreach($valveItems as $item)
                             @php $code = $item->code; $val = $logData[$code] ?? ($log->$code ?? null); @endphp
                             <tr>
                                <td class="ps-3">{{ $item->label }}</td>
                                <td class="text-center">
                                     @include('admin.reports.partials.badge', ['status' => $val ?: '-'])
                                </td>
                             </tr>
                        @endforeach

                        <!-- SECTION D: IBOX SYSTEM (Hardcoded Legacy) -->
                        <tr class="table-secondary"><th colspan="2">D. IBOX SYSTEM</th></tr>
                        <tr><td class="ps-3">IBOX Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->ibox_condition])</td></tr>
                        <tr><td class="ps-3">Battery</td><td class="text-center">{{ $log->ibox_battery_percent ? $log->ibox_battery_percent.'%' : '-' }}</td></tr>
                        <tr><td class="ps-3">Pressure (Digital)</td><td class="text-center">{{ $log->ibox_pressure ?? '-' }}</td></tr>
                        
                        <tr>
                            <td class="ps-3">Temperature #1 (Digital)</td>
                            <td class="text-center">
                                {{ $log->ibox_temperature_1 ?? $log->ibox_temperature ?? '-' }}
                                @if($log->ibox_temperature_1_timestamp)
                                <br><small class="text-muted">({{ $log->ibox_temperature_1_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-3">Temperature #2 (Digital)</td>
                            <td class="text-center">
                                {{ $log->ibox_temperature_2 ?? '-' }}
                                @if($log->ibox_temperature_2_timestamp)
                                <br><small class="text-muted">({{ $log->ibox_temperature_2_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>

                        <tr><td class="ps-3">Level (Digital)</td><td class="text-center">{{ $log->ibox_level ?? '-' }}</td></tr>

                        <!-- SECTION E: INSTRUMENTS (Hardcoded Legacy) -->
                        <tr class="table-secondary"><th colspan="2">E. INSTRUMENTS</th></tr>
                        <tr><td class="ps-3">Pressure Gauge Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->pressure_gauge_condition])</td></tr>
                        <tr><td class="ps-3 text-muted ms-3">Serial Number</td><td class="text-center">{{ $log->pressure_gauge_serial_number ?? '-' }}</td></tr>
                        <tr><td class="ps-3 text-muted ms-3">Calibration Date</td><td class="text-center">{{ $log->pressure_gauge_calibration_date ? $log->pressure_gauge_calibration_date->format('Y-m-d') : '-' }}</td></tr>
                        
                        <tr>
                            <td class="ps-3 text-muted ms-3">Reading (Pressure 1)</td>
                            <td class="text-center">
                                {{ $log->pressure_1 ? (float)$log->pressure_1.' MPa' : '-' }}
                                @if($log->pressure_1_timestamp)
                                <br><small class="text-muted">({{ $log->pressure_1_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-3 text-muted ms-3">Reading (Pressure 2)</td>
                            <td class="text-center">
                                {{ $log->pressure_2 ? (float)$log->pressure_2.' MPa' : '-' }}
                                @if($log->pressure_2_timestamp)
                                <br><small class="text-muted">({{ $log->pressure_2_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>
                        
                        <tr><td class="ps-3">Level Gauge Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->level_gauge_condition])</td></tr>
                        <tr>
                            <td class="ps-3 text-muted ms-3">Reading (Level 1)</td>
                            <td class="text-center">
                                {{ $log->level_1 ? (float)$log->level_1.' %' : '-' }}
                                @if($log->level_1_timestamp)
                                <br><small class="text-muted">({{ $log->level_1_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-3 text-muted ms-3">Reading (Level 2)</td>
                            <td class="text-center">
                                {{ $log->level_2 ? (float)$log->level_2.' %' : '-' }}
                                @if($log->level_2_timestamp)
                                <br><small class="text-muted">({{ $log->level_2_timestamp->format('H:i') }})</small>
                                @endif
                            </td>
                        </tr>

                        <!-- SECTION F: VACUUM SYSTEM (Hardcoded Legacy) -->
                        <tr class="table-secondary"><th colspan="2">F. VACUUM SYSTEM</th></tr>
                        <tr><td class="ps-3">Vacuum Gauge Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_gauge_condition])</td></tr>
                        <tr><td class="ps-3">Port Suction Condition</td><td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->vacuum_port_suction_condition])</td></tr>
                        <tr><td class="ps-3">Vacuum Value</td><td class="text-center fw-bold">{{ $log->vacuum_value ? (float)$log->vacuum_value . ' mTorr' : '-' }}</td></tr>
                        <tr><td class="ps-3">Vacuum Temperature</td><td class="text-center">{{ $log->vacuum_temperature ? $log->vacuum_temperature . ' C' : '-' }}</td></tr>
                        <tr><td class="ps-3">Check Datetime</td><td class="text-center">{{ $log->vacuum_check_datetime ? $log->vacuum_check_datetime->format('Y-m-d H:i') : '-' }}</td></tr>

                        <!-- SECTION G: PSV (Hardcoded Legacy) -->
                        <tr class="table-secondary"><th colspan="2">G. PSV (PRESSURE SAFETY VALVES)</th></tr>
                        @foreach(['psv1', 'psv2', 'psv3', 'psv4'] as $p)
                            <tr>
                                <td class="ps-3 fw-bold">{{ strtoupper($p) }} Condition</td>
                                <td class="text-center">@include('admin.reports.partials.badge', ['status' => $log->{$p.'_condition'}])</td>
                            </tr>
                            <tr>
                                <td class="ps-3 text-muted small">
                                    STATUS: {{ strtoupper($log->{$p.'_status'} ?? '-') }} | SN: {{ $log->{$p.'_serial_number'} ?? '-' }}
                                    <br>Cal. Date: {{ $log->{$p.'_calibration_date'} ? $log->{$p.'_calibration_date'}->format('Y-m-d') : '-' }}
                                </td>
                                <td class="text-center small">Valid Until: {{ $log->{$p.'_valid_until'} ? $log->{$p.'_valid_until'}->format('Y-m-d') : '-' }}</td>
                            </tr>
                        @endforeach

                        <!-- SIGNATURES -->
                        <tr class="table-dark"><th colspan="2">SIGNATURES</th></tr>
                        
                        <!-- Inspector Signature -->
                        <tr>
                            <td class="ps-3 fw-bold">Inspector Signature</td>
                            <td class="text-center">
                                @php
                                    // Inspector Signature Logic
                                    $inspSigPath = $log->inspector->signature_path ?? null;
                                    $inspSigUrl = null;
                                    if ($inspSigPath && \Storage::disk('public')->exists($inspSigPath)) {
                                        $inspSigUrl = asset('storage/' . $inspSigPath);
                                    }
                                @endphp
                                
                                @if($inspSigUrl)
                                    <img src="{{ $inspSigUrl }}" alt="Inspector Signature" style="max-height: 80px; max-width: 200px; border: 1px solid #eee;">
                                @else
                                    <span class="text-muted fst-italic">No Digital Signature</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $log->inspector->name ?? 'Unknown Inspector' }}</small>
                            </td>
                        </tr>

                        <!-- Receiver Signature (Outgoing Only) -->
                        @if($log->inspection_type === 'outgoing_inspection')
                        <tr>
                            <td class="ps-3 fw-bold">Receiver Signature</td>
                            <td class="text-center">
                                @php
                                    // Receiver Signature Logic (One-time event)
                                    $recvSigPath = $log->receiver_signature_path ?? null;
                                    $recvSigUrl = null;
                                    if ($recvSigPath && \Storage::disk('public')->exists($recvSigPath)) {
                                        $recvSigUrl = asset('storage/' . $recvSigPath);
                                    }
                                @endphp
                                
                                @if($recvSigUrl)
                                    <img src="{{ $recvSigUrl }}" alt="Receiver Signature" style="max-height: 80px; max-width: 200px; border: 1px solid #eee;">
                                @else
                                    <span class="text-muted fst-italic">{{ $log->receiver_confirmed_at ? 'Confirmed but no signature' : 'Waiting for confirmation...' }}</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $log->receiver_name ?? 'Unknown Receiver' }}</small>
                            </td>
                        </tr>
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
