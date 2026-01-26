<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report - {{ $isotank->iso_number ?? 'UNKNOWN' }}</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: sans-serif; font-size: 7.5pt; margin: 0; padding: 0; color: #333; line-height: 1.1; }
        
        .header { text-align: center; margin-bottom: 2px; }
        .header img { width: 100%; height: auto; max-height: 45px; object-fit: contain; } 
        
        .title-box { text-align: center; color: black; font-weight: bold; padding: 2px; font-size: 9.5pt; margin-bottom: 4px; border: 1px solid #ccc; background-color: #e0f7fa; }
        .title-box.outgoing { background-color: #e8f5e9; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; font-size: 7.5pt; }
        .info-table td { border: 1px solid #ddd; padding: 2px 4px; }
        .label { background-color: #f5f5f5; font-weight: bold; width: 15%; }
        
        .section-title { background-color: #eee; font-weight: bold; font-size: 8pt; padding: 1px 4px; margin-bottom: 1px; border-left: 3px solid #333; margin-top: 3px; }
        
        .checklist-table { width: 100%; border-collapse: collapse; font-size: 7pt; margin-bottom: 1px; }
        .checklist-table td { border-bottom: 1px solid #eee; padding: 1px 2px; vertical-align: middle; }
        .checklist-table th { background-color: #f0f0f0; padding: 1px 2px; font-weight: bold; border-bottom: 1px solid #ccc; text-align: left; font-size: 7pt; }
        
        .status-badge { padding: 1px 3px; border-radius: 2px; color: white; font-weight: bold; font-size: 6.5pt; display: inline-block; min-width: 35px; text-align: center; text-transform: uppercase; }
        .bg-green { background-color: #2e7d32; }
        .bg-red { background-color: #c62828; }
        .bg-orange { background-color: #ef6c00; }
        .bg-grey { background-color: #9e9e9e; }
        
        .signature-section { margin-top: 5px; width: 100%; page-break-inside: avoid; }
        .sig-box { float: left; width: 45%; margin-right: 5%; }
        .sig-line { border-top: 1px solid #000; margin-top: 20px; padding-top: 2px; font-weight: bold; font-size: 8pt; }
        .sig-label { font-size: 7.5pt; margin-bottom: 2px; }
        
        .page-break { page-break-after: always; }
        .clearfix::after { content: ""; clear: both; display: table; }

        /* Photo Grid */
        .photo-page-title { text-align: center; font-weight: bold; font-size: 11pt; margin-bottom: 15px; padding: 5px; border-bottom: 2px solid #333; }
        .photo-grid { width: 100%; text-align: center; }
        .photo-item { width: 48%; display: inline-block; vertical-align: top; margin-bottom: 15px; margin-right: 1%; margin-left:1%; box-sizing: border-box; border: 1px solid #ddd; padding: 5px; background: #fff; border-radius: 4px; }
        .photo-item img { width: 100%; height: 180px; object-fit: contain; background-color: #fcfcfc; border: 1px solid #eee; margin-bottom: 5px; }
        .photo-label { font-weight: bold; font-size: 8.5pt; color: #444; }
    </style>
</head>
<body>
    
    {{-- PAGE 1: DETAILS & CHECKLIST --}}
    
    <!-- HEADER -->
    <div class="header">
        @if(file_exists(public_path('assets/images/header_kln.png')))
            <img src="{{ public_path('assets/images/header_kln.png') }}" alt="KLN Header">
        @else
            <h3 style="margin:0; padding:5px;">PT KAYAN LNG NUSANTARA</h3>
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
            <td class="label">ISO Number</td><td style="font-size: 9pt;"><b>{{ $isotank->iso_number ?? '-' }}</b></td>
            <td class="label">Product</td><td>{{ $isotank->product ?? '-' }}</td>
            <td class="label">Filling Status</td>
            <td style="font-weight: bold; color: #0056b3;">
                {{ $inspection->filling_status_desc ?? ($isotank->filling_status_desc ?? '-') }}
            </td>
        </tr>
        <tr>
            <td class="label">Owner</td><td>{{ $isotank->owner ?? '-' }}</td>
            <td class="label">Location</td><td>{{ $isotank->location ?? '-' }}</td>
            <td class="label">Insp. Date</td><td>{{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('d M Y') : '-' }}</td>
        </tr>
    </table>

    {{-- T11 DIAGRAM (USER REQUEST) --}}
    @if(($isotank->tank_category ?? '') === 'T11')
        <div style="text-align: center; margin: 5px 0; border: 1px solid #eee; padding: 2px;">
            @php $diagramPath = public_path('assets/images/t11_diagram.png'); @endphp
            @if(file_exists($diagramPath))
                <img src="{{ $diagramPath }}" style="width: 100%; max-height: 150px; object-fit: contain;">
            @else
                <div style="color: #999; font-style: italic;">[ T11 ISO TANK DIAGRAM ]</div>
            @endif
        </div>
    @endif

    {{-- Helper PHP --}}
    @php
        function badge($val) {
            $val = strtolower($val ?? '');
            if (!$val || $val == 'null') return "<span class='status-badge bg-grey'>-</span>";
            
            $map = [
                'good' => ['green', 'GOOD'],
                'not_good' => ['red', 'NOT GOOD'], 'bad' => ['red', 'BAD'],
                'need_attention' => ['orange', 'ATTN'],
                'correct' => ['green', 'CORRECT'], 'incorrect' => ['red', 'INCORRECT'],
                'yes' => ['green', 'YES'], 'no' => ['red', 'NO'],
                'valid' => ['green', 'VALID'], 'expired' => ['red', 'EXPIRED'],
                'na' => ['grey', 'N/A']
            ];
            
            [$cls, $txt] = $map[$val] ?? ['grey', strtoupper($val)];
            return "<span class='status-badge bg-$cls'>$txt</span>";
        }

        // Parse Inspection Data
        $jsonData = [];
        if (!empty($inspection->inspection_data)) {
            $jsonData = is_string($inspection->inspection_data) ? json_decode($inspection->inspection_data, true) : $inspection->inspection_data;
            if (!is_array($jsonData)) $jsonData = [];
        }

        // Fetch Items
        $masterItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order', 'asc')->get();

        // Filter STRICTLY by tank category
        $tankCat = $isotank->tank_category ?? 'T75';
        $applicableItems = $masterItems->filter(fn($i) => in_array($tankCat, $i->applicable_categories ?? []));
        
        // Grouping for Display
        if ($tankCat == 'T75') {
            // T75: Use legacy B/C layout
            $itemsB = $applicableItems->filter(fn($i) => in_array($i->category, ['b', 'external', 'general']));
            $itemsC = $applicableItems->filter(fn($i) => in_array($i->category, ['c', 'valve', 'piping']) || empty($i->category));
            $groupedItems = [];
        } else {
            // T11/T50: Use dynamic grouping
            $groupedItems = $applicableItems->groupBy('category');
            $itemsB = collect();
            $itemsC = collect();
        }

        // Legacy Map for Fallback (Same as Web)
        $legacyMap = [
            'Surface Condition' => 'surface', 'Tank Surface & Paint Condition' => 'surface',
            'Frame Condition' => 'frame', 'Frame Structure' => 'frame',
            'Tank Name Plate' => 'tank_plate', 'Data Plate' => 'tank_plate',
            'Venting Pipe' => 'venting_pipe',
            'Explosion Proof Cover' => 'explosion_proof_cover',
            'Safety Label' => 'safety_label', 'DG 1972 GHS MSA_Safety_label' => 'safety_label',
            'Document Container' => 'document_container',
            'Valve Box Door' => 'valve_box_door',
            'Grounding System' => 'grounding_system',
            'Valve Condition' => 'valve_condition',
            'Valve Position' => 'valve_position',
            'Pipe Joint' => 'pipe_joint',
            'Air Source Connection' => 'air_source_connection',
            'ESDV' => 'esdv',
            'Blind Flange' => 'blind_flange',
            'PRV' => 'prv'
        ];

        // Fetch Receiver Confirmations if outgoing
        $recvConfirmations = collect();
        if ($type === 'outgoing') {
            $recvConfirmations = \App\Models\ReceiverConfirmation::where('inspection_log_id', $inspection->id)->get()->keyBy('item_name');
        }
    @endphp

    @if($tankCat == 'T75')
        {{-- T75 LEGACY 2-COLUMN LAYOUT (RESTORED) --}}
        <table style="width: 100%; border-collapse: collapse; margin-top: 0;">
            <tr>
                <!-- LEFT COLUMN: B (General), D (IBOX), F (Vacuum) -->
                <td style="width: 49%; vertical-align: top; padding-right: 5px; border: none;">
                    
                    <div class="section-title">B. GENERAL CONDITION</div>
                    <table class="checklist-table">
                        @foreach($itemsB as $item)
                            @php 
                                $val = $inspection->{$item->code} ?? ($jsonData[$item->code] ?? null); 
                                if(!$val && isset($legacyMap[$item->label])) {
                                    $lKey = $legacyMap[$item->label];
                                    $val = $inspection->{$lKey} ?? ($jsonData[$lKey] ?? null);
                                }
                            @endphp
                            <tr>
                                <td style="width: 70%;">{{ $item->label }}</td>
                                <td style="text-align: right;">{!! badge($val) !!}</td>
                            </tr>
                        @endforeach

                        {{-- UNMAPPED ITEMS LOGIC --}}
                        @php
                            $standardCodes = $masterItems->pluck('code')->toArray();
                            foreach($jsonData as $k => $v) {
                                if(!in_array($k, $standardCodes) && 
                                   !in_array($k, ['inspection_date', 'inspector_name', 'filling_status', 'remarks', 'signature', 'longitude', 'latitude', 'location_name']) &&
                                   is_string($v) && strlen($v) < 50) {
                                     if(!str_contains($k, 'ibox') && !str_contains($k, 'vacuum') && !str_contains($k, 'pressure_gauge') && !str_contains($k, 'psv')) {
                        @endphp
                            <tr>
                                <td style="width: 70%;">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                                <td style="text-align: right;">{!! badge($v) !!}</td>
                            </tr>
                        @php
                                     }
                                }
                            }
                        @endphp
                    </table>
                    
                    {{-- D. IBOX SYSTEM (T75 ONLY) --}}
                    @if(!empty($inspection->ibox_condition) || !empty($inspection->ibox_pressure))
                    <div class="section-title">D. IBOX SYSTEM</div>
                    <table class="checklist-table">
                        <tr><td style="width: 70%;">Condition</td><td style="text-align:right">{!! badge($inspection->ibox_condition) !!}</td></tr>
                        <tr><td>Battery</td><td style="text-align:right">{{ $inspection->ibox_battery_percent ? $inspection->ibox_battery_percent.'%' : '-' }}</td></tr>
                        <tr><td>Pressure (Digital)</td><td style="text-align:right">{{ $inspection->ibox_pressure ?? '-' }}</td></tr>
                        <tr>
                             <td>Temp #1 (Digital)</td>
                             <td style="text-align:right">
                                 {{ $inspection->ibox_temperature_1 ?? $inspection->ibox_temperature ?? '-' }}
                                 @if($inspection->ibox_temperature_1_timestamp)
                                 <br><small style="color:#666; font-size:6.5pt;">({{ \Carbon\Carbon::parse($inspection->ibox_temperature_1_timestamp)->format('H:i') }})</small>
                                 @endif
                             </td>
                        </tr>
                        <tr>
                             <td>Temp #2 (Digital)</td>
                             <td style="text-align:right">
                                 {{ $inspection->ibox_temperature_2 ?? '-' }}
                                 @if($inspection->ibox_temperature_2_timestamp)
                                 <br><small style="color:#666; font-size:6.5pt;">({{ \Carbon\Carbon::parse($inspection->ibox_temperature_2_timestamp)->format('H:i') }})</small>
                                 @endif
                             </td>
                        </tr>
                        <tr><td>Level (Digital)</td><td style="text-align:right">{{ $inspection->ibox_level ?? '-' }}</td></tr>
                    </table>
                    @endif

                    {{-- F. VACUUM SYSTEM (T75 ONLY) --}}
                    @if(!empty($inspection->vacuum_gauge_condition) || !empty($inspection->vacuum_value))
                     <div class="section-title">F. VACUUM SYSTEM</div>
                     <table class="checklist-table">
                        <tr><td>Gauge / Port</td><td style="text-align:right">{!! badge($inspection->vacuum_gauge_condition) !!} / {!! badge($inspection->vacuum_port_suction_condition) !!}</td></tr>
                        <tr><td colspan="2" style="color: #666;">
                            Value: {{ $inspection->vacuum_value ? (is_numeric($inspection->vacuum_value) ? number_format((float)$inspection->vacuum_value, 2) : $inspection->vacuum_value) : '-' }} {{ $inspection->vacuum_unit ?? 'mtorr' }}
                            @if($inspection->vacuum_temperature) ({{ $inspection->vacuum_temperature }} °C) @endif
                        </td></tr>
                        @if($inspection->vacuum_check_datetime)
                        <tr><td colspan="2" style="color:#666; font-size:6pt;">
                             Check Date: {{ \Carbon\Carbon::parse($inspection->vacuum_check_datetime)->format('d M Y H:i') }}
                        </td></tr>
                        @endif
                     </table>
                     @endif
                </td>
                
                <!-- RIGHT COLUMN: C (Valves), E (Instruments), G (PSV) -->
                <td style="width: 49%; vertical-align: top; padding-left: 5px; border: none;">
                    <div class="section-title">C. VALVES & PIPING</div>
                     <table class="checklist-table">
                        @foreach($itemsC as $item)
                            @php 
                                $val = $inspection->{$item->code} ?? ($jsonData[$item->code] ?? null); 
                                if(!$val && isset($legacyMap[$item->label])) {
                                    $lKey = $legacyMap[$item->label];
                                    $val = $inspection->{$lKey} ?? ($jsonData[$lKey] ?? null);
                                }
                            @endphp
                            <tr>
                                <td style="width: 70%;">{{ $item->label }}</td>
                                <td style="text-align: right;">{!! badge($val) !!}</td>
                            </tr>
                        @endforeach
                    </table>

                    {{-- E. INSTRUMENTS --}}
                    @if(!empty($inspection->pressure_gauge_condition) || !empty($inspection->pressure_1))
                    <div class="section-title">E. INSTRUMENTS</div>
                    <table class="checklist-table">
                        <tr><td><b>Pressure Gauge</b></td><td style="text-align: right;">{!! badge($inspection->pressure_gauge_condition) !!}</td></tr>
                        <tr><td colspan="2" style="color: #666; font-size: 6.5pt;">
                            #1: {{ $inspection->pressure_1 ? $inspection->pressure_1.' MPa' : '-' }} @if($inspection->pressure_1_timestamp) ({{ \Carbon\Carbon::parse($inspection->pressure_1_timestamp)->format('H:i') }}) @endif
                            <br>
                            #2: {{ $inspection->pressure_2 ? $inspection->pressure_2.' MPa' : '-' }} @if($inspection->pressure_2_timestamp) ({{ \Carbon\Carbon::parse($inspection->pressure_2_timestamp)->format('H:i') }}) @endif
                        </td></tr>
                        
                        <tr><td><b>Level Gauge</b></td><td style="text-align: right;">{!! badge($inspection->level_gauge_condition) !!}</td></tr>
                        <tr><td colspan="2" style="color: #666; font-size: 6.5pt;">
                             #1: {{ $inspection->level_1 ? $inspection->level_1.' %' : '-' }} @if($inspection->level_1_timestamp) ({{ \Carbon\Carbon::parse($inspection->level_1_timestamp)->format('H:i') }}) @endif
                             <br>
                             #2: {{ $inspection->level_2 ? $inspection->level_2.' %' : '-' }} @if($inspection->level_2_timestamp) ({{ \Carbon\Carbon::parse($inspection->level_2_timestamp)->format('H:i') }}) @endif
                        </td></tr>
                    </table>
                    @endif

                    {{-- G/PSV --}}
                    @if(!empty($inspection->psv1_condition) || !empty($inspection->psv2_condition) || !empty($inspection->psv3_condition) || !empty($inspection->psv4_condition))
                     <div class="section-title">G. SAFETY VALVES (PSV)</div>
                     <table class="checklist-table">
                        @for($i=1; $i<=4; $i++)
                            @php $cond = $inspection->{"psv{$i}_condition"}; @endphp
                            @if($cond)
                            <tr>
                                <td><b>PSV #{{ $i }}</b> <small>({{ $inspection->{"psv{$i}_serial_number"} ?? '-' }})</small></td>
                                <td style="text-align: right;">{!! badge($cond) !!}</td>
                            </tr>
                            @endif
                        @endfor
                     </table>
                     @endif
                </td>
            </tr>
        </table>
    @else
        {{-- T11/T50 DYNAMIC 3-COLUMN LAYOUT (COMPACT) --}}
        @php
            $receiverCodes = \App\Services\PdfGenerationService::getGeneralConditionItems($tankCat);
        @endphp

        <div style="margin-top: 5px;">
            <table class="checklist-table" style="border: 1px solid #ddd; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50%; border: 1px solid #ddd; background: #f5f5f5; font-size: 6.5pt;">DESCRIPTION</th>
                        <th style="width: 25%; border: 1px solid #ddd; text-align: center; background: #f5f5f5; font-size: 6.5pt;">INSPECTOR</th>
                        <th style="width: 25%; border: 1px solid #ddd; text-align: center; background: #f5f5f5; font-size: 6.5pt;">RECEIVER</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applicableItems->groupBy('category') as $catName => $items)
                        <tr>
                            <td colspan="3" class="section-title" style="margin: 0; background-color: #f9f9f9; border: 1px solid #ddd; font-size: 7pt; padding: 1px 3px;">
                                {{ strtoupper($catName) }}
                            </td>
                        </tr>
                        @foreach($items as $item)
                            @php 
                                $val = $inspection->{$item->code} ?? ($jsonData[$item->code] ?? null); 
                                if(!$val && isset($legacyMap[$item->label])) {
                                    $lKey = $legacyMap[$item->label];
                                    $val = $inspection->{$lKey} ?? ($jsonData[$lKey] ?? null);
                                }
                                
                                $isConfirmedItem = in_array($item->code, $receiverCodes);
                                $conf = $recvConfirmations[$item->code] ?? null;
                            @endphp
                            <tr style="line-height: 1;">
                                <td style="border: 1px solid #eee; padding: 1px 3px; font-size: 6.8pt;">
                                    {{ $item->label }}
                                    @if($conf && $conf->receiver_remark)
                                        <div style="font-size: 5.5pt; color: #666; font-style: italic;">
                                            Note: {{ $conf->receiver_remark }}
                                        </div>
                                    @endif
                                </td>
                                <td style="border: 1px solid #eee; text-align: center; vertical-align: middle; padding: 1px;">
                                    {!! badge($val) !!}
                                </td>
                                <td style="border: 1px solid #eee; text-align: center; vertical-align: middle; padding: 1px;">
                                    @if($type === 'outgoing')
                                        @if($isConfirmedItem)
                                            @if($conf)
                                                <span class="status-badge {{ $conf->receiver_decision === 'ACCEPT' ? 'bg-green' : 'bg-red' }}" style="font-size: 5.5pt; min-width: 30px;">
                                                    {{ $conf->receiver_decision }}
                                                </span>
                                            @else
                                                <span class="status-badge bg-grey" style="font-size: 5.5pt; min-width: 30px;">WAITING</span>
                                            @endif
                                        @else
                                            <span style="color: #bbb; font-size: 6pt;">N/A</span>
                                        @endif
                                    @else
                                        <span style="color: #bbb; font-size: 6pt;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- OUTGOING: RECEIVER CONFIRMATION SUMMARY --}}
        @if($type === 'outgoing')
            <div style="font-size: 7pt; color: #333; margin-top: 5px; padding: 4px; border: 1px solid #c8e6c9; background-color: #f1f8e9;">
                "I, <strong>{{ $inspection->receiver_name ?? ($job->receiver_name ?? 'N/A') }}</strong>, hereby confirm that I have reviewed the inspector's findings listed in the sections above and accept the current condition of the isotank as documented."
            </div>
        @endif
    @endif

    {{-- SIGNATURES --}}
    <div class="signature-section clearfix">
        <div class="sig-box">
            <div class="sig-label">Inspector Signature</div>
            
            <div style="height: 60px; margin-bottom: 5px;">
                @php
                    $inspSigPath = $inspector->signature_path ?? ($inspection->inspectionJob->inspector->signature_path ?? null);
                    $inspFullPath = null;
                    if ($inspSigPath) {
                        if (file_exists(public_path('storage/' . $inspSigPath))) $inspFullPath = public_path('storage/' . $inspSigPath);
                        elseif (file_exists(storage_path('app/public/' . $inspSigPath))) $inspFullPath = storage_path('app/public/' . $inspSigPath);
                    }
                @endphp
                
                @if($inspFullPath)
                    <img src="{{ $inspFullPath }}" style="max-height: 60px; max-width: 150px;">
                @else
                    <div style="height: 60px;"></div> {{-- Spacer if no signature --}}
                @endif
            </div>

            <div class="sig-line">
                {{ $inspector->name ?? ($inspection->inspectionJob->inspector->name ?? ($inspection->inspector_name ?? '.......................')) }}
                <br><small style="font-weight: normal;">Date: {{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('d M Y') : '-' }}</small>
            </div>
        </div>
        
        @if($type === 'outgoing')
        <div class="sig-box">
             <div class="sig-label">Receiver Signature</div>
             
             <div style="height: 60px; margin-bottom: 5px;">
                @php
                    $recvSigPath = $inspection->receiver_signature_path ?? null;
                    $recvFullPath = null;
                    if ($recvSigPath) {
                        if (file_exists(public_path('storage/' . $recvSigPath))) $recvFullPath = public_path('storage/' . $recvSigPath);
                        elseif (file_exists(storage_path('app/public/' . $recvSigPath))) $recvFullPath = storage_path('app/public/' . $recvSigPath);
                    }
                @endphp
                
                @if($recvFullPath)
                    <img src="{{ $recvFullPath }}" style="max-height: 60px; max-width: 150px;">
                @else
                    <div style="height: 60px;"></div>
                @endif
            </div>
            
            <div class="sig-line">
                {{ $job->receiver_name ?? ($inspection->receiver_name ?? '.......................') }}
                <br><small style="font-weight: normal;">
                    Date: {{ $inspection->receiver_signed_at ? \Carbon\Carbon::parse($inspection->receiver_signed_at)->format('d M Y H:i') : (isset($generatedAt) ? $generatedAt->format('d M Y') : date('d M Y')) }}
                </small>
            </div>
        </div>
        @endif
    </div>

    {{-- PAGE BREAK --}}
    <div class="page-break"></div>

    {{-- PAGE 2: PHOTOS --}}
    <div class="photo-page-title">INSPECTION PHOTOS</div>

    <div class="photo-grid">
        @php
            $photos = [
                'Front View' => $inspection->photo_front,
                'Back View' => $inspection->photo_back,
                'Left View' => $inspection->photo_left,
                'Right View' => $inspection->photo_right,
                'Inside Valve Box' => $inspection->photo_inside_valve_box,
                'Additional' => $inspection->photo_additional,
                'Extra' => $inspection->photo_extra,
            ];
        @endphp

        @foreach($photos as $label => $path)
            @if($path)
                @php
                    $fullPath = null;
                    if (file_exists(public_path('storage/' . $path))) {
                        $fullPath = public_path('storage/' . $path);
                    } elseif (file_exists(storage_path('app/public/' . $path))) {
                         $fullPath = storage_path('app/public/' . $path);
                    }
                @endphp

                @if($fullPath)
                <div class="photo-item">
                    <img src="{{ $fullPath }}" alt="{{ $label }}">
                    <div class="photo-label">{{ $label }}</div>
                </div>
                @endif
            @endif
        @endforeach
        
        {{-- Outgoing Receiver Photos --}}
        @if($type === 'outgoing' && isset($receiverConfirmations))
             @foreach($receiverConfirmations as $key => $conf)
                @if($conf->receiver_photo_path)
                    @php
                        $fullPath = null;
                         if (file_exists(public_path('storage/' . $conf->receiver_photo_path))) {
                            $fullPath = public_path('storage/' . $conf->receiver_photo_path);
                        } elseif (file_exists(storage_path('app/public/' . $conf->receiver_photo_path))) {
                             $fullPath = storage_path('app/public/' . $conf->receiver_photo_path);
                        }
                    @endphp
                     @if($fullPath)
                    <div class="photo-item">
                         <img src="{{ $fullPath }}" alt="Receiver Photo">
                         <div class="photo-label">Receiver: {{ \App\Services\PdfGenerationService::getItemDisplayName($key) }}</div>
                    </div>
                    @endif
                @endif
             @endforeach
        @endif
    </div>

</body>
</html>
