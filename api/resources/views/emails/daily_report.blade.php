<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
        .header { background-color: #0d47a1; color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        
        /* Summary Boxes */
        .summary-container { display: flex; justify-content: space-between; margin: 20px 0; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; }
        .summary-box { text-align: center; width: 24%; border-right: 1px solid #ddd; }
        .summary-box:last-child { border-right: none; }
        .sum-number { font-size: 28px; font-weight: bold; color: #0d47a1; display: block; margin-bottom: 5px; }
        .sum-label { font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Tables */
        h3 { color: #2c3e50; border-bottom: 2px solid #0d47a1; padding-bottom: 10px; margin-top: 30px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th { background-color: #f1f4f8; color: #495057; font-weight: 600; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background-color: #f8f9fa; }
        
        /* Status Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .bg-danger { background-color: #ffebee; color: #c62828; }
        .bg-warning { background-color: #fff3e0; color: #ef6c00; }
        .bg-success { background-color: #e8f5e9; color: #2e7d32; }
        
        .btn-link { background-color: #0d47a1; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <h2 style="margin:0;">PT KAYAN LNG NUSANTARA</h2>
        <p style="margin:5px 0 0; opacity: 0.9;">Daily Isotank Operations Report</p>
        <p style="margin:5px 0 0; font-size: 14px; opacity: 0.8;">{{ $date }}</p>
    </div>

    <!-- NEW OPERATIONAL KPIs -->
    <div style="margin: 20px 0; padding: 15px; background-color: #e3f2fd; border-radius: 8px; border: 1px solid #bbdefb; display: flex; justify-content: space-around; text-align: center;">
        <div style="width: 33%;">
            <span style="display: block; font-size: 24px; font-weight: bold; color: #1565c0;">{{ $summary['inspections_today'] }}</span>
            <span style="font-size: 12px; color: #546e7a; text-transform: uppercase;">Inspections Submitted Today</span>
        </div>
        <div style="width: 33%; border-left: 1px solid #bbdefb; border-right: 1px solid #bbdefb;">
            <span style="display: block; font-size: 24px; font-weight: bold; color: #1565c0;">{{ $summary['open_maintenance'] }}</span>
            <span style="font-size: 12px; color: #546e7a; text-transform: uppercase;">Total Open Maintenance</span>
        </div>
        <div style="width: 33%;">
            <span style="display: block; font-size: 24px; font-weight: bold; color: #1565c0;">{{ $summary['calibration_progress'] }}%</span>
            <span style="font-size: 12px; color: #546e7a; text-transform: uppercase;">Calibration Progress</span>
        </div>
    </div>

    <!-- 1. MOVEMENT SUMMARY -->
    <div class="summary-container">
        <div class="summary-box" style="width: 20%;">
            <span class="sum-number">{{ $summary['incoming'] }}</span>
            <div style="font-size: 10px; color: #666; margin-bottom: 3px;">{{ $summary['incoming_details'] ?? '' }}</div>
            <span class="sum-label">Incoming<br>(Gate-In)</span>
        </div>
        <div class="summary-box" style="width: 20%;">
            <span class="sum-number">{{ $summary['outgoing_started'] }}</span>
            <div style="font-size: 10px; color: #666; margin-bottom: 3px;">{{ $summary['outgoing_started_details'] ?? '' }}</div>
            <span class="sum-label" title="Process started by Admin">Outgoing<br>(Started)</span>
        </div>
        <div class="summary-box" style="width: 20%;">
            <span class="sum-number">{{ $summary['outgoing_official'] }}</span>
            <div style="font-size: 10px; color: #666; margin-bottom: 3px;">{{ $summary['outgoing_official_details'] ?? '' }}</div>
            <span class="sum-label" title="Receiver Confirmed">Official Out<br>(Completed)</span>
        </div>
        <div class="summary-box" style="width: 20%; background-color: #e8f5e9; border: 2px solid #43a047;">
        <span class="sum-number" style="color: #2e7d32; font-size: 32px;">{{ $summary['stock_site'] }}</span>
        <div style="font-size: 10px; color: #2e7d32; margin-bottom: 3px; font-weight: bold;">{{ $summary['stock_site_details'] ?? '' }}</div>
        <span class="sum-label" style="color: #1b5e20; font-weight: bold;">Stock at Site<br>(SMGRS)</span>
    </div>
        <div class="summary-box" style="width: 20%;">
            <span class="sum-number">{{ $summary['stock_other'] }}</span>
            <div style="font-size: 10px; color: #666; margin-bottom: 3px;">{{ $summary['stock_other_details'] ?? '' }}</div>
            <span class="sum-label">Other<br>Locations</span>
        </div>
    </div>

    <!-- 2. FILLING STATUS BREAKDOWN (SYSTEM OCCUPANCY) -->
    <h3 style="margin-top: 30px; border-bottom: 2px solid #0d47a1; color: #0d47a1;">📊 System Occupancy Status</h3>
    @if(!empty($summary['filling_status_breakdown']))
    <div style="background-color: #fff; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">
        <table style="width: 100%; margin: 0;">
            <tr style="background-color: #f8f9fa;">
                <th style="width: 70%; text-align: left; padding: 12px 20px; color: #495057;">Status Code</th>
                <th style="width: 30%; text-align: right; padding: 12px 20px; color: #495057;">Count</th>
            </tr>
            @foreach($summary['filling_status_breakdown'] as $status => $count)
            <tr style="border-bottom: 1px solid #f1f1f1;">
                <td style="padding: 10px 20px;">
                    <span style="font-weight: 600; color: #333;">{{ $status }}</span>
                </td>
                <td style="text-align: right; padding: 10px 20px;">
                    <span style="background-color: #e3f2fd; color: #0d47a1; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 13px;">{{ $count }}</span>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @else
    <p style="color: #666; font-style: italic;">No status data available.</p>
    @endif

    <!-- PYTHON ANALYTICS: STOCK CHART -->
    @if(isset($summary['stock_chart']) && !empty($summary['stock_chart']))
        <h3 style="margin-top: 30px; border-bottom: 2px solid #0d47a1; color: #0d47a1;">📈 Occupancy Visualization (Analytics)</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
            <pre style="font-family: Consolas, 'Courier New', monospace; font-size: 12px; line-height: 1.4; color: #333; margin: 0; white-space: pre-wrap;">{{ $summary['stock_chart'] }}</pre>
        </div>
    @endif

    <!-- 3. HIGHLIGHT MASALAH (EXCEPTION REPORT) -->
    @if(count($issues) > 0)
    <h3 style="color: #c62828; border-bottom-color: #c62828;">⚠️ Exception Report (Needs Attention)</h3>
    <table style="border: 1px solid #ffebee;">
        <thead>
            <tr style="background-color: #ffebee;">
                <th style="color: #c62828;">ISO Number</th>
                <th style="color: #c62828;">Issue Found</th>
            </tr>
        </thead>
        <tbody>
            @foreach($issues as $issue)
            <tr>
                <td style="font-weight: bold;">{{ $issue['iso_number'] }}</td>
                <td style="color: #d32f2f;">{{ $issue['notes'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- 4. ATTACHMENT NOTICE -->
    <div style="margin-top: 40px; padding: 20px; background-color: #f1f8e9; border: 1px solid #c8e6c9; border-radius: 8px; text-align: center;">
        <h4 style="margin: 0 0 10px; color: #2e7d32;">📥 Detailed Data Attached</h4>
        <p style="margin: 0; font-size: 14px; color: #558b2f;">
            Please refer to the attached Excel file for the complete list of:
            <br>• Daily Inspection Activities (Log)
            <br>• Detailed Maintenance Jobs (Open & Completed)
            <br>• Calibration Records
        </p>
    </div>

    <div class="footer">
        <p>This is an automated system message. Please do not reply directly to this email.</p>
        <p>&copy; {{ date('Y') }} PT Kayan LNG Nusantara - Isotank Information System</p>
    </div>

</body>
</html>
