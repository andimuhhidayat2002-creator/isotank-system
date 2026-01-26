<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report - {{ $isotank->iso_number ?? 'UNKNOWN' }}</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: sans-serif; font-size: 8pt; margin: 0; padding: 0; color: #333; line-height: 1.1; }
        
        .header { text-align: center; margin-bottom: 2px; }
        .header img { width: 100%; height: auto; max-height: 45px; object-fit: contain; } 
        
        .title-box { text-align: center; color: black; font-weight: bold; padding: 3px; font-size: 10pt; margin-bottom: 5px; border: 1px solid #ccc; background-color: #e0f7fa; }
        .title-box.outgoing { background-color: #e8f5e9; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; font-size: 7.5pt; }
        .info-table td { border: 1px solid #ddd; padding: 2px 4px; }
        .label { background-color: #f5f5f5; font-weight: bold; width: 15%; }
        
        .section-title { background-color: #eee; font-weight: bold; font-size: 8.5pt; padding: 2px 5px; margin-bottom: 2px; border-left: 4px solid #333; margin-top: 4px; }
        
        .checklist-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 2px; }
        .checklist-table td { border-bottom: 1px solid #eee; padding: 1px 3px; vertical-align: middle; }
        .checklist-table th { background-color: #f0f0f0; padding: 2px 3px; font-weight: bold; border-bottom: 1px solid #ccc; text-align: left; font-size: 7.5pt; }
        
        .status-badge { padding: 1px 3px; border-radius: 2px; color: white; font-weight: bold; font-size: 6.5pt; display: inline-block; min-width: 35px; text-align: center; text-transform: uppercase; }
        .bg-green { background-color: #2e7d32; }
        .bg-red { background-color: #c62828; }
        .bg-orange { background-color: #ef6c00; }
        .bg-grey { background-color: #9e9e9e; }
        
        .signature-section { margin-top: 10px; width: 100%; page-break-inside: avoid; }
        .sig-box { float: left; width: 45%; margin-right: 5%; }
        .sig-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 2px; font-weight: bold; font-size: 8pt; }
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
        <div style="text-align: center; margin: 10px 0; border: 1px solid #eee; padding: 5px;">
            @php $diagramPath = public_path('assets/images/t11_diagram.png'); @endphp
            @if(file_exists($diagramPath))
                <img src="{{ $diagramPath }}" style="width: 100%; max-height: 200px; object-fit: contain;">
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
    @endphp

    @if($tankCat == 'T75')
        {{-- T75 LEGACY 2-COLUMN LAYOUT --}}
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
                                <td style="text-align: right;">
                                    {{-- Avoid double badges for outgoing; Receiver Confirmation table takes priority --}}
                                    @if($type === 'incoming')
                                        {!! badge($val) !!}
                                    @else
                                        <small style="color: #666; font-weight: bold;">{{ strtoupper($val ?? '-') }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        {{-- UNMAPPED ITEMS LOGIC --}}
                        @php
                            $standardCodes = $masterItems->pluck('code')->toArray();
                            foreach($jsonData as $k => $v) {
                                if(!in_array($k, $standardCodes) && 
                                   !in_array($k, ['inspection_date', 'inspector_name', 'filling_status', 'remarks', 'signature', 'longitude', 'latitude', 'location_name']) &&
                                   is_string($v) && strlen($v) < 50) {
                                     // Exclude hardcoded legacy fields
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
                                 <br><small style="color:#666; font-size:6pt;">({{ \Carbon\Carbon::parse($inspection->ibox_temperature_1_timestamp)->format('H:i') }})</small>
                                 @endif
                             </td>
                        </tr>
                        <tr>
                             <td>Temp #2 (Digital)</td>
                             <td style="text-align:right">
                                 {{ $inspection->ibox_temperature_2 ?? '-' }}
                                 @if($inspection->ibox_temperature_2_timestamp)
                                 <br><small style="color:#666; font-size:6pt;">({{ \Carbon\Carbon::parse($inspection->ibox_temperature_2_timestamp)->format('H:i') }})</small>
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
                        <tr><td colspan="2" style="color: #666; padding-left: 5px;">
                            Value: {{ $inspection->vacuum_value ? number_format((float)$inspection->vacuum_value, 2) : '-' }} {{ $inspection->vacuum_unit ?? 'mtorr' }}
                            ({{ $inspection->vacuum_temperature ?? '-' }} °C)
                        </td></tr>
                        <tr><td colspan="2" style="color: #666; padding-left: 5px;">
                            Check Date: {{ $inspection->vacuum_check_datetime ? \Carbon\Carbon::parse($inspection->vacuum_check_datetime)->format('d M Y H:i') : '-' }}
                        </td></tr>
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
                                <td style="text-align: right;">
                                    @if($type === 'incoming')
                                        {!! badge($val) !!}
                                    @else
                                        <small style="color: #666; font-weight: bold;">{{ strtoupper($val ?? '-') }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>

                    {{-- E. INSTRUMENTS (T75 ONLY) --}}
                    @if(!empty($inspection->pressure_gauge_condition) || !empty($inspection->pressure_1))
                    <div class="section-title">E. INSTRUMENTS</div>
                    <table class="checklist-table">
                        <tr>
                            <td><b>Pressure Gauge</b></td>
                            <td style="text-align: right;">{!! badge($inspection->pressure_gauge_condition) !!}</td>
                        </tr>
                        <tr><td colspan="2" style="color: #666; padding-left: 5px;">
                            #1: {{ $inspection->pressure_1 ? $inspection->pressure_1.' MPa' : '-' }}
                            @if($inspection->pressure_1_timestamp)
                                <small>({{ \Carbon\Carbon::parse($inspection->pressure_1_timestamp)->format('H:i') }})</small>
                            @endif
                            <br>
                            #2: {{ $inspection->pressure_2 ? $inspection->pressure_2.' MPa' : '-' }}
                            @if($inspection->pressure_2_timestamp)
                                <small>({{ \Carbon\Carbon::parse($inspection->pressure_2_timestamp)->format('H:i') }})</small>
                            @endif
                        </td></tr>
                        
                        <tr>
                            <td><b>Level Gauge</b></td>
                            <td style="text-align: right;">{!! badge($inspection->level_gauge_condition) !!}</td>
                        </tr>
                        <tr><td colspan="2" style="color: #666; padding-left: 5px;">
                             #1: {{ $inspection->level_1 ? $inspection->level_1.' %' : '-' }}
                            @if($inspection->level_1_timestamp)
                                <small>({{ \Carbon\Carbon::parse($inspection->level_1_timestamp)->format('H:i') }})</small>
                            @endif
                            <br>
                             #2: {{ $inspection->level_2 ? $inspection->level_2.' %' : '-' }}
                            @if($inspection->level_2_timestamp)
                                <small>({{ \Carbon\Carbon::parse($inspection->level_2_timestamp)->format('H:i') }})</small>
                            @endif
                        </td></tr>
                    </table>
                    @endif

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
        {{-- T11/T50 DYNAMIC LAYOUT --}}
        @php
            $categoryNames = $groupedItems->keys();
            $midPoint = ceil($categoryNames->count() / 2);
            $leftCategories = $categoryNames->slice(0, $midPoint);
            $rightCategories = $categoryNames->slice($midPoint);
        @endphp

        <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
            <tr>
                <!-- LEFT COLUMN -->
                <td style="width: 49%; vertical-align: top; padding-right: 5px; border: none;">
                    @foreach($leftCategories as $catName)
                        <div class="section-title">{{ strtoupper($catName) }}</div>
                        <table class="checklist-table">
                            @foreach($groupedItems[$catName] as $item)
                                @php 
                                    $val = $inspection->{$item->code} ?? ($jsonData[$item->code] ?? null); 
                                    if(!$val && isset($legacyMap[$item->label])) {
                                        $lKey = $legacyMap[$item->label];
                                        $val = $inspection->{$lKey} ?? ($jsonData[$lKey] ?? null);
                                    }
                                @endphp
                                <tr>
                                    <td style="width: 70%;">{{ $item->label }}</td>
                                    <td style="text-align: right;">
                                        @if($type === 'incoming')
                                            {!! badge($val) !!}
                                        @else
                                            <small style="color: #666; font-weight: bold;">{{ strtoupper($val ?? '-') }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endforeach
                </td>

                <!-- RIGHT COLUMN -->
                <td style="width: 49%; vertical-align: top; padding-left: 5px; border: none;">
                    @foreach($rightCategories as $catName)
                        <div class="section-title">{{ strtoupper($catName) }}</div>
                        <table class="checklist-table">
                            @foreach($groupedItems[$catName] as $item)
                                @php 
                                    $val = $inspection->{$item->code} ?? ($jsonData[$item->code] ?? null); 
                                    if(!$val && isset($legacyMap[$item->label])) {
                                        $lKey = $legacyMap[$item->label];
                                        $val = $inspection->{$lKey} ?? ($jsonData[$lKey] ?? null);
                                    }
                                @endphp
                                <tr>
                                    <td style="width: 70%;">{{ $item->label }}</td>
                                    <td style="text-align: right;">
                                        @if($type === 'incoming')
                                            {!! badge($val) !!}
                                        @else
                                            <small style="color: #666; font-weight: bold;">{{ strtoupper($val ?? '-') }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    {{-- OUTGOING: RECEIVER CONFIRMATION TABLE --}}
    @if($type === 'outgoing' && isset($receiverConfirmations))
        <div class="section-title" style="background-color: #e8f5e9; border-left-color: #2e7d32; margin-top: 8px;">FINAL RECEIVER CONFIRMATION & ACCEPTANCE</div>
        <div style="font-size: 7.5pt; color: #555; margin-bottom: 5px; font-style: italic;">
            "I hereby confirm that I have reviewed the inspector's findings below and accept the current condition of the isotank."
        </div>
        <table class="checklist-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Item</th>
                    <th style="width: 15%; text-align: center;">Insp. Cond.</th>
                    <th style="width: 15%; text-align: center;">Receiver Decision</th>
                    <th style="width: 45%;">Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Services\PdfGenerationService::getGeneralConditionItems($tankCat) as $key)
                    @php
                        $label = \App\Services\PdfGenerationService::getItemDisplayName($key);
                        $inspectorVal = $inspection->$key ?? ($jsonData[$key] ?? null);
                        $conf = $receiverConfirmations[$key] ?? null;
                        
                        $decisionBadge = '-';
                        if ($conf) {
                            $decisionBadge = $conf->receiver_decision === 'ACCEPT' 
                                ? "<span class='status-badge bg-green'>ACCEPT</span>" 
                                : "<span class='status-badge bg-red'>REJECT</span>";
                        }
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align: center;">{!! badge($inspectorVal) !!}</td>
                        <td style="text-align: center;">{!! $decisionBadge !!}</td>
                        <td style="color: #555; font-style: italic;">{{ Str::limit($conf->receiver_remark ?? '-', 60) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
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
