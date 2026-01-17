<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report - {{ $isotank->iso_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 9pt; margin: 0; padding: 0; color: #333; }
        
        /* HEADER FIX - Responsive Width */
        .header { text-align: center; margin-bottom: 15px; }
        .header img { 
            width: 100%; 
            height: auto; 
            max-height: 80px; 
            object-fit: contain; 
        } 
        
        .title-box { text-align: center; color: black; font-weight: bold; padding: 8px; font-size: 11pt; margin-bottom: 15px; border: 1px solid #ccc; background-color: #e0f7fa; }
        .title-box.outgoing { background-color: #e8f5e9; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9pt; }
        .info-table td { border: 1px solid #ddd; padding: 6px 8px; }
        .label { background-color: #f5f5f5; font-weight: bold; width: 18%; }
        
        .section-title { background-color: #eee; font-weight: bold; font-size: 10pt; padding: 5px 8px; margin-bottom: 8px; border-left: 4px solid #333; margin-top: 15px; }
        
        .checklist-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 10px; }
        .checklist-table td { border-bottom: 1px solid #eee; padding: 4px 6px; vertical-align: top; }
        
        .status-badge { padding: 3px 6px; border-radius: 3px; color: white; font-weight: bold; font-size: 8pt; display: inline-block; min-width: 50px; text-align: center; }
        .bg-green { background-color: #2e7d32; }
        .bg-red { background-color: #c62828; }
        .bg-orange { background-color: #ef6c00; }
        .bg-grey { background-color: #9e9e9e; }
        
        .signature-section { margin-top: 50px; width: 100%; }
        .sig-box { float: left; width: 45%; margin-right: 5%; }
        .sig-line { border-top: 1px solid #000; margin-top: 60px; padding-top: 10px; line-height: 1.5; }

        .clearfix::after { content: ""; clear: both; display: table; }
        
        .footer { font-size: 8pt; color: #888; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    
    <!-- HEADER -->
    <div class="header">
        @if(file_exists(public_path('assets/images/header_kln.png')))
            <img src="{{ public_path('assets/images/header_kln.png') }}" alt="KLN Header">
        @else
            <h2 style="margin:0; padding:10px;">PT KAYAN LNG NUSANTARA</h2>
        @endif
    </div>

    <!-- TITLE -->
    <div class="title-box {{ $type == 'outgoing' ? 'outgoing' : '' }}">
        {{ strtoupper($type == 'outgoing' ? 'OUTGOING' : 'INCOMING') }} INSPECTION REPORT
    </div>

    <!-- A. DATA OF TANK -->
    <div class="section-title" style="margin-top: 0;">A. DATA OF TANK</div>
    <table class="info-table">
        <tr>
            <td class="label">ISO Number</td><td><b>{{ $isotank->iso_number }}</b></td>
            <td class="label">Product</td><td>{{ $isotank->product ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Owner</td><td>{{ $isotank->owner ?? '-' }}</td>
            <td class="label">Location</td><td>{{ $isotank->location ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Insp. Date</td><td>{{ $inspection->inspection_date ? $inspection->inspection_date->format('d M Y') : '-' }}</td>
            <td class="label">Inspector</td>
            <td>
                {{-- FIX: Robust Inspector Name Retrieval --}}
                <b>{{ $inspector->name ?? ($inspection->inspectionJob->inspector->name ?? ($inspection->inspector_name ?? '-')) }}</b>
            </td>
        </tr>
        <tr>
            <td class="label">Filling Status</td>
            <td colspan="3"><b>{{ $inspection->filling_status_desc ?? ($isotank->filling_status_desc ?? 'Not Specified') }}</b></td>
        </tr>
    </table>

    {{-- Helper PHP --}}
    @php
        function badge($val) {
            $val = strtolower($val ?? '');
            $cls = 'grey'; $txt = 'N/A';
            if ($val == 'good') { $cls = 'green'; $txt = 'GOOD'; }
            elseif ($val == 'not_good' || $val == 'bad') { $cls = 'red'; $txt = 'NOT GOOD'; }
            elseif ($val == 'need_attention') { $cls = 'orange'; $txt = 'ATTENTION'; }
            elseif ($val == 'correct') { $cls = 'green'; $txt = 'CORRECT'; }
            elseif ($val == 'incorrect') { $cls = 'red'; $txt = 'INCORRECT'; }
            elseif (!empty($val) && $val != 'na' && $val != 'null') { $cls = 'grey'; $txt = strtoupper($val); }
            return "<span class='status-badge bg-$cls'>$txt</span>";
        }
    @endphp

    {{-- LAYOUT SPLIT --}}
    @if($type !== 'outgoing')
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- LEFT COLUMN -->
            <td style="width: 48%; vertical-align: top; padding-right: 15px;">
                
                <div class="section-title">B. GENERAL CONDITION</div>
                <table class="checklist-table">
                    @php
                        // Standard items
                        $sectionB = ['surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover', 'grounding_system', 'document_container', 'safety_label', 'valve_box_door', 'valve_box_door_handle'];
                        
                        // Merge with dynamic items if inspection_data exists
                        $dynamicItems = [];
                        if (!empty($inspection->inspection_data) && is_array($inspection->inspection_data)) {
                             // This assumes keys in inspection_data are item codes. 
                             // We should ideally fetch from Master InspectionItem model, but in view we rely on data presence.
                             foreach($inspection->inspection_data as $k => $v) {
                                 // Simple logic: if value is a condition string and not a standard column, treat as dynamic item
                                 // Filter out photos, timestamps, remarks, etc.
                                 if (in_array($v, ['good', 'not_good', 'need_attention', 'na']) && !in_array($k, array_merge($sectionB, ['valve_condition','valve_position','pipe_joint','air_source_connection','esdv','blind_flange','prv']))) {
                                     // Also filter out section C items temporarily hardcoded to avoid dupes if logic expands
                                     $dynamicItems[] = $k; 
                                 }
                             }
                        }
                        
                        // Combine and unique
                        $allItemsB = array_unique(array_merge($sectionB, $dynamicItems));
                    @endphp

                    @foreach($allItemsB as $key)
                    @php 
                        // Get value from column OR json
                        $val = $inspection->$key ?? ($inspection->inspection_data[$key] ?? null);
                        if ($val) {
                    @endphp
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td style="text-align: right;">{!! badge($val) !!}</td>
                    </tr>
                    @php } @endphp
                    @endforeach
                </table>

                <div class="section-title">C. VALVES & PIPING</div>
                 <table class="checklist-table">
                    @foreach(['valve_condition', 'valve_position', 'pipe_joint', 'air_source_connection', 'esdv', 'blind_flange', 'prv'] as $key)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                        <td style="text-align: right;">{!! badge($inspection->$key) !!}</td>
                    </tr>
                    @endforeach
                </table>

                 <div class="section-title">D. IBOX SYSTEM</div>
                 <table class="checklist-table">
                    <tr><td>Condition</td><td style="text-align:right">{!! badge($inspection->ibox_condition) !!}</td></tr>
                    <tr><td>Battery</td><td style="text-align:right">{{ $inspection->ibox_battery_percent ?? '-' }} %</td></tr>
                    <tr><td>Pressure / Temp</td><td style="text-align:right">{{ $inspection->ibox_pressure ?? '-' }} / {{ $inspection->ibox_temperature ?? '-' }}</td></tr>
                </table>

            </td>
            
            <!-- RIGHT COLUMN -->
            <td style="width: 48%; vertical-align: top; padding-left: 15px;">
                
                 <div class="section-title">E. INSTRUMENTS</div>
                 <table class="checklist-table">
                     <!-- PG -->
                    <tr>
                        <td style="border-bottom:none;"><b>Pressure Gauge</b></td>
                        <td style="text-align: right; border-bottom:none;">{!! badge($inspection->pressure_gauge_condition) !!}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 8pt; color: #555; padding-left: 10px; padding-bottom: 8px;">
                            SN: <b>{{ $inspection->pressure_gauge_serial_number ?? $inspection->pg_serial_number ?? '-' }}</b><br>
                            Cal. Date: {{ $inspection->pressure_gauge_calibration_date ? \Carbon\Carbon::parse($inspection->pressure_gauge_calibration_date)->format('d M Y') : '-' }}<br>
                            Reading: <b>{{ $inspection->pressure_1 ?? '-' }} MPa</b>
                        </td>
                    </tr>
                    
                    <!-- LG -->
                    <tr>
                        <td style="border-bottom:none;"><b>Level Gauge</b></td>
                        <td style="text-align: right; border-bottom:none;">{!! badge($inspection->level_gauge_condition) !!}</td>
                    </tr>
                    <tr><td colspan="2" style="font-size: 8pt; color: #555; padding-left: 10px; padding-bottom: 8px;">Reading: <b>{{ $inspection->level_1 ?? '-' }} %</b></td></tr>
                 </table>

                 <div class="section-title">F. VACUUM SYSTEM</div>
                 <table class="checklist-table">
                    <tr><td>Vacuum Gauge</td><td style="text-align:right">{!! badge($inspection->vacuum_gauge_condition) !!}</td></tr>
                    <tr><td>Port Suction</td><td style="text-align:right">{!! badge($inspection->vacuum_port_suction_condition) !!}</td></tr>
                    <tr><td colspan="2" style="font-size: 8pt; color: #555; padding-left: 10px;">
                        Value: <b>{{ $inspection->vacuum_value ?? '-' }} {{ $inspection->vacuum_unit ?? 'torr' }}</b> ({{ $inspection->vacuum_temperature ?? '-' }} °C) <br>
                        Check Date: {{ $inspection->vacuum_check_datetime ? \Carbon\Carbon::parse($inspection->vacuum_check_datetime)->format('d M Y') : '-' }}
                    </td></tr>
                 </table>

                 <div class="section-title">G. SAFETY VALVES (PSV)</div>
                 <table class="checklist-table">
                    @for($i=1; $i<=4; $i++)
                        @php 
                            $sn = $inspection->{"psv{$i}_serial_number"};
                            $cond = $inspection->{"psv{$i}_condition"};
                            $cal = $inspection->{"psv{$i}_calibration_date"};
                        @endphp
                        @if($sn || $cond)
                        <tr>
                            <td style="border-bottom:none;"><b>PSV #{{ $i }}</b></td>
                            <td style="text-align: right; border-bottom:none;">{!! badge($cond) !!}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-size: 8pt; color: #555; padding-left: 10px; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
                                SN: <b>{{ $sn ?? '-' }}</b><br>
                                Cal: {{ $cal ? \Carbon\Carbon::parse($cal)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                        @endif
                    @endfor
                 </table>

            </td>
        </tr>
    </table>
    @endif

    {{-- OUTGOING LAYOUT (SINGLE COLUMN TO ACCOMMODATE RECEIVER FIELDS) --}}
    @if($type === 'outgoing')
        <div class="section-title">B. GENERAL CONDITION</div>
        <table class="checklist-table">
            @foreach(['surface', 'frame', 'tank_plate', 'venting_pipe', 'explosion_proof_cover', 'grounding_system', 'document_container', 'safety_label', 'valve_box_door', 'valve_box_door_handle'] as $key)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td style="text-align: right;">{!! badge($inspection->$key) !!}</td>
                </tr>
            @endforeach
        </table>
        {{-- ... dst (sederhana untuk outgoing karena space lebih lega) ... --}}
    @endif

    <!-- NOTES -->
    @if($inspection->maintenance_notes)
    <div style="margin-top: 20px; border: 1px solid #ddd; padding: 10px; background: #fffde7;">
        <b>MAINTENANCE NOTES:</b><br>
        {{ $inspection->maintenance_notes }}
    </div>
    @endif

    <!-- SIGNATURES -->
    <div class="signature-section clearfix">
        <div class="sig-box">
            Inspector Signature
            <div class="sig-line">
                <b>{{ $inspector->name ?? ($inspection->inspectionJob->inspector->name ?? ($inspection->inspector_name ?? '.......................')) }}</b><br>
                Date: {{ $inspection->inspection_date->format('d M Y') }}
            </div>
        </div>
        
        @if($type === 'outgoing')
        <div class="sig-box">
            Receiver Signature
            <div class="sig-line">
                <b>{{ $inspection->receiver_name ?? '.......................' }}</b><br>
                Date: {{ $inspection->receiver_confirmed_at ? \Carbon\Carbon::parse($inspection->receiver_confirmed_at)->format('d M Y') : '.......................' }}
            </div>
        </div>
        @endif
    </div>

</body>
</html>
