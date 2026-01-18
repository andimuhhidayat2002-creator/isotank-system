<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report - {{ $isotank->iso_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 8pt; margin: 0; padding: 0; color: #333; }
        
        .header { text-align: center; margin-bottom: 5px; }
        .header img { width: 100%; height: auto; max-height: 50px; object-fit: contain; } 
        
        .title-box { text-align: center; color: black; font-weight: bold; padding: 4px; font-size: 10pt; margin-bottom: 10px; border: 1px solid #ccc; background-color: #e0f7fa; }
        .title-box.outgoing { background-color: #e8f5e9; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8pt; }
        .info-table td { border: 1px solid #ddd; padding: 3px 6px; }
        .label { background-color: #f5f5f5; font-weight: bold; width: 15%; }
        
        .section-title { background-color: #eee; font-weight: bold; font-size: 9pt; padding: 3px 5px; margin-bottom: 4px; border-left: 4px solid #333; margin-top: 8px; }
        
        .checklist-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 5px; }
        .checklist-table td { border-bottom: 1px solid #eee; padding: 2px 4px; vertical-align: middle; }
        
        .status-badge { padding: 2px 4px; border-radius: 3px; color: white; font-weight: bold; font-size: 7pt; display: inline-block; min-width: 40px; text-align: center; }
        .bg-green { background-color: #2e7d32; }
        .bg-red { background-color: #c62828; }
        .bg-orange { background-color: #ef6c00; }
        .bg-grey { background-color: #9e9e9e; }
        
        .signature-section { margin-top: 20px; width: 100%; page-break-inside: avoid; }
        .sig-box { float: left; width: 45%; margin-right: 5%; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; line-height: 1.2; }

        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    
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
            <td class="label">ISO Number</td><td><b>{{ $isotank->iso_number }}</b></td>
            <td class="label">Product</td><td>{{ $isotank->product ?? '-' }}</td>
            <td class="label">Filling Status</td>
            <td><b>{{ $inspection->filling_status_desc ?? ($isotank->filling_status_desc ?? '-') }}</b></td>
        </tr>
        <tr>
            <td class="label">Owner</td><td>{{ $isotank->owner ?? '-' }}</td>
            <td class="label">Location</td><td>{{ $isotank->location ?? '-' }}</td>
            <td class="label">Insp. Date</td><td>{{ $inspection->inspection_date ? $inspection->inspection_date->format('d M Y') : '-' }}</td>
        </tr>
    </table>

    {{-- Helper PHP --}}
    @php
        function badge($val) {
            $val = strtolower($val ?? '');
            $cls = 'grey'; $txt = 'N/A';
            if ($val == 'good') { $cls = 'green'; $txt = 'GOOD'; }
            elseif ($val == 'not_good' || $val == 'bad') { $cls = 'red'; $txt = 'NOT GOOD'; }
            elseif ($val == 'need_attention') { $cls = 'orange'; $txt = 'ATTN'; }
            elseif ($val == 'correct') { $cls = 'green'; $txt = 'CORRECT'; }
            elseif ($val == 'incorrect') { $cls = 'red'; $txt = 'INCORRECT'; }
            elseif (!empty($val) && $val != 'na' && $val != 'null') { $cls = 'grey'; $txt = strtoupper($val); }
            return "<span class='status-badge bg-$cls'>$txt</span>";
        }
    @endphp

    <div class="header-title" style="background-color: #f0f0f0; margin-bottom: 5px; text-align: center; font-weight: bold; border: 1px solid #ddd;">
        {{ strtoupper($type == 'outgoing' ? 'OUTGOING' : 'INCOMING') }} INSPECTION REPORT
    </div>

    {{-- MAIN INSPECTION DATA (Shown for BOTH Incoming and Outgoing) --}}
    @php
        // ... (existing logic)
        $jsonData = [];
        if (!empty($inspection->inspection_data)) {
            $jsonData = is_string($inspection->inspection_data) ? json_decode($inspection->inspection_data, true) : $inspection->inspection_data;
            if (!is_array($jsonData)) $jsonData = [];
        }

        // 2. Fetch Master Items from DB
        $masterItems = \App\Models\InspectionItem::where('is_active', true)->orderBy('order', 'asc')->get();
        // SECTION B: Includes 'b', 'external' (like safety_label, tank_plate)
        $itemsB = $masterItems->filter(function($item) {
            return in_array($item->category, ['b', 'external', 'general']);
        });
        
        // SECTION C: Includes 'c', 'valve' (like pipe_joint, Pressure_regulator), and NULL/Empty (fallback)
        $itemsC = $masterItems->filter(function($item) {
             return in_array($item->category, ['c', 'valve', 'piping']) || empty($item->category);
        });
    @endphp

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- LEFT COLUMN (Section B) -->
            <td style="width: 49%; vertical-align: top; padding-right: 10px; border: none;">
                
                <div class="section-title">B. GENERAL CONDITION</div>
                <table class="checklist-table">
                    @forelse($itemsB as $item)
                        @php 
                            $key = $item->code;
                            $val = $inspection->$key ?? ($jsonData[$key] ?? null);
                        @endphp
                        <tr>
                            <td style="width: 70%;">{{ $item->label }}</td>
                            <td style="text-align: right;">{!! $val ? badge($val) : '<span style="color:#aaa">-</span>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2">No items.</td></tr>
                    @endforelse
                </table>
                
                @if($inspection->ibox_condition)
                 <div class="section-title" style="margin-top: 5px;">D. IBOX SYSTEM</div>
                 <table class="checklist-table">
                    <tr><td style="width: 70%;">Condition</td><td style="text-align:right">{!! badge($inspection->ibox_condition) !!}</td></tr>
                    <tr><td>Battery</td><td style="text-align:right">{{ $inspection->ibox_battery_percent ?? '-' }} %</td></tr>
                    <tr><td>Press/Temp</td><td style="text-align:right">{{ $inspection->ibox_pressure ?? '-' }} / {{ $inspection->ibox_temperature ?? '-' }}</td></tr>
                </table>
                @endif

                 <div class="section-title" style="margin-top: 5px;">E. INSTRUMENTS</div>
                 <table class="checklist-table">
                    <tr>
                        <td style="border-bottom:none;"><b>Pressure Gauge</b></td>
                        <td style="text-align: right; border-bottom:none;">{!! badge($inspection->pressure_gauge_condition) !!}</td>
                    </tr>
                    <tr><td colspan="2" style="font-size: 7pt; color: #555; padding-left: 5px;">Reading: {{ $inspection->pressure_1 ?? '-' }} MPa</td></tr>
                    
                    <tr>
                        <td style="border-bottom:none;"><b>Level Gauge</b></td>
                        <td style="text-align: right; border-bottom:none;">{!! badge($inspection->level_gauge_condition) !!}</td>
                    </tr>
                    <tr><td colspan="2" style="font-size: 7pt; color: #555; padding-left: 5px;">Reading: {{ $inspection->level_1 ?? '-' }} %</td></tr>
                 </table>

            </td>
            
            <!-- RIGHT COLUMN (Section C) -->
            <td style="width: 49%; vertical-align: top; padding-left: 10px; border: none;">
                
                 <div class="section-title">C. VALVES & PIPING</div>
                 <table class="checklist-table">
                    @forelse($itemsC as $item)
                        @php 
                            $key = $item->code;
                            $val = $inspection->$key ?? ($jsonData[$key] ?? null);
                        @endphp
                        <tr>
                            <td style="width: 70%;">{{ $item->label }}</td>
                            <td style="text-align: right;">{!! $val ? badge($val) : '<span style="color:#aaa">-</span>' !!}</td>
                        </tr>
                    @empty
                         <tr><td colspan="2">No items.</td></tr>
                    @endforelse
                </table>



                 <div class="section-title">F. VACUUM SYSTEM</div>
                 <table class="checklist-table">
                    <tr><td>Gauge / Port</td><td style="text-align:right">{!! badge($inspection->vacuum_gauge_condition) !!} / {!! badge($inspection->vacuum_port_suction_condition) !!}</td></tr>
                    <tr><td colspan="2" style="font-size: 7pt; color: #555; padding-left: 5px;">
                        Value: {{ $inspection->vacuum_value ?? '-' }} {{ $inspection->vacuum_unit ?? 'torr' }} ({{ $inspection->vacuum_temperature ?? '-' }} °C)
                    </td></tr>
                 </table>

                 <div class="section-title">G. SAFETY VALVES (PSV)</div>
                 <table class="checklist-table">
                    @for($i=1; $i<=4; $i++)
                        @php $cond = $inspection->{"psv{$i}_condition"}; @endphp
                        @if($cond)
                        <tr>
                            <td style="border-bottom:none;"><b>PSV #{{ $i }}</b> <span style="font-size:7pt; color:#666">({{ $inspection->{"psv{$i}_serial_number"} ?? '-' }})</span></td>
                            <td style="text-align: right; border-bottom:none;">{!! badge($cond) !!}</td>
                        </tr>
                        @endif
                    @endfor
                 </table>

            </td>
        </tr>
    </table>

    {{-- OUTGOING RECEIVER CONFIRMATION --}}
    @if($type === 'outgoing')
        <div style="page-break-inside: avoid;">
            <div class="header-title" style="background-color: #e8f5e9; margin: 10px 0 5px 0; text-align: center; font-weight: bold; border: 1px solid #ddd;">
                RECEIVER CONFIRMATION (GENERAL CONDITION)
            </div>
        <table class="checklist-table">
            <thead>
                <tr style="background-color: #eee;">
                    <th style="text-align: left; padding: 4px; border-bottom: 1px solid #999;">Item</th>
                    <th style="text-align: center; padding: 4px; border-bottom: 1px solid #999;">Inspector Cond.</th>
                    <th style="text-align: center; padding: 4px; border-bottom: 1px solid #999;">Receiver Decision</th>
                    <th style="text-align: left; padding: 4px; border-bottom: 1px solid #999;">Remark</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Get standard + dynamic items using the service
                    $generalItems = \App\Services\PdfGenerationService::getGeneralConditionItems();
                @endphp
                @foreach($generalItems as $key)
                    @php
                        $label = \App\Services\PdfGenerationService::getItemDisplayName($key);
                        $inspectorVal = $inspection->$key ?? null;
                        
                        // Get receiver confirmation data
                        $conf = $receiverConfirmations[$key] ?? null;
                        $decision = $conf ? $conf->receiver_decision : '-';
                        $remark = $conf ? $conf->receiver_remark : '-';
                        
                        $decisionBadge = '-';
                        if($decision === 'ACCEPT') {
                            $decisionBadge = '<span class="status-badge bg-green">ACCEPT</span>';
                        } elseif($decision === 'REJECT') {
                            $decisionBadge = '<span class="status-badge bg-red">REJECT</span>';
                        }
                    @endphp
                    <tr>
                        <td style="padding: 4px;">{{ $label }}</td>
                        <td style="text-align: center; padding: 4px;">{!! badge($inspectorVal) !!}</td>
                        <td style="text-align: center; padding: 4px;">{!! $decisionBadge !!}</td>
                        <td style="padding: 4px; font-style: italic; color: #555;">{{ $remark }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif

    <!-- SIGNATURES (Compact) -->
    <!-- SIGNATURES (Compact) -->
    <div class="signature-section clearfix">
        <div class="sig-box">
            Inspector Signature
            <div class="sig-line">
                <b>{{ $inspector->name ?? ($inspection->inspectionJob->inspector->name ?? ($inspection->inspector_name ?? '.......................')) }}</b><br>
                Date: {{ $inspection->inspection_date ? $inspection->inspection_date->format('d M Y') : '-' }}
            </div>
        </div>
        
        @if($type === 'outgoing')
        <div class="sig-box">
            Receiver Signature
            <div class="sig-line">
                <b>{{ $job->receiver_name ?? '.......................' }}</b><br>
                Date: {{ isset($generatedAt) ? $generatedAt->format('d M Y') : date('d M Y') }}
            </div>
        </div>
        @endif
    </div>

</body>
</html>
