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

    <!-- 1. MOVEMENT SUMMARY -->
    <div class="summary-container">
        <div class="summary-box">
            <span class="sum-number">{{ $summary['incoming'] }}</span>
            <span class="sum-label">Incoming Today</span>
        </div>
        <div class="summary-box">
            <span class="sum-number">{{ $summary['outgoing'] }}</span>
            <span class="sum-label">Outgoing Today</span>
        </div>
        <div class="summary-box">
            <span class="sum-number">{{ $summary['stock_site'] }}</span>
            <span class="sum-label">Stock at Site</span>
        </div>
        <div class="summary-box">
            <span class="sum-number">{{ $summary['stock_other'] }}</span>
            <span class="sum-label">Other Locations</span>
        </div>
    </div>

    <!-- 2. HIGHLIGHT MASALAH (EXCEPTION REPORT) -->
    @if(count($issues) > 0)
    <h3 style="color: #c62828; border-bottom-color: #c62828;">⚠️ Exception Report (Needs Attention)</h3>
    <p>Isotank berikut ditemukan memiliki masalah/kerusakan pada inspeksi hari ini:</p>
    <table>
        <thead>
            <tr>
                <th>ISO Number</th>
                <th>Inspection Type</th>
                <th>Issue Found</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($issues as $issue)
            <tr>
                <td><strong>{{ $issue['iso_number'] }}</strong></td>
                <td>{{ ucfirst(str_replace('_', ' ', $issue['type'])) }}</td>
                <td style="color: #c62828;">{{ $issue['notes'] ?? 'Multiple conditions flagged as Not Good' }}</td>
                <td>
                    <span class="badge bg-warning">CHECK MAINTENANCE</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <h3>✅ Exception Report</h3>
    <p style="color: #2e7d32; font-style: italic;">No critical issues reported in today's inspections.</p>
    @endif

    <!-- 3. INSPECTION REPORTS WITH PDF LINKS -->
    <h3>📄 Inspection Activity & Reports</h3>
    @if(count($inspectionLogs) > 0)
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>ISO Number</th>
                <th>Type</th>
                <th>Inspector</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inspectionLogs as $log)
            <tr>
                <td>{{ $log->created_at->format('H:i') }}</td>
                <td><strong>{{ $log->isotank->iso_number }}</strong></td>
                <td>
                    @if($log->inspection_type == 'incoming_inspection')
                        <span class="badge bg-success">INCOMING</span>
                    @else
                        <span class="badge bg-warning">OUTGOING</span>
                    @endif
                </td>
                <td>{{ $log->inspector->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #888; font-style: italic;">No inspection activities recorded today.</p>
    @endif

    <!-- 4. MAINTENANCE UPDATE -->
    <h3>🔧 Maintenance Updates</h3>
    
    <!-- Completed Today -->
    <h4 style="margin-bottom: 5px; font-size: 15px;">Completed Today</h4>
    @if(count($maintenance['completed']) > 0)
    <table>
        <thead>
            <tr>
                <th>ISO Number</th>
                <th>Item / Description</th>
                <th>Technician</th>
            </tr>
        </thead>
        <tbody>
            @foreach($maintenance['completed'] as $job)
            <tr>
                <td>{{ $job->isotank->iso_number }}</td>
                <td>{{ $job->source_item }} - {{ Str::limit($job->description, 30) }}</td>
                <td>{{ $job->completedBy->name ?? 'Unknown' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size: 13px; color: #888;">No maintenance jobs completed today.</p>
    @endif

    <!-- Outstanding -->
    <h4 style="margin-bottom: 5px; font-size: 15px; margin-top: 20px; color: #e65100;">Outstanding Jobs (> 3 Days)</h4>
    @if(count($maintenance['outstanding']) > 0)
    <table>
        <thead>
            <tr>
                <th>ISO Number</th>
                <th>Pending Since</th>
                <th>Days Open</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($maintenance['outstanding'] as $job)
            <tr>
                <td><strong>{{ $job->isotank->iso_number }}</strong></td>
                <td>{{ $job->created_at->format('d M Y') }}</td>
                <td style="color: #c62828; font-weight: bold;">{{ $job->created_at->diffInDays(now()) }} Days</td>
                <td><span class="badge bg-warning">{{ strtoupper($job->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size: 13px; color: #2e7d32;">No long-overdue maintenance jobs.</p>
    @endif


    <div class="footer">
        <p>This is an automated system message. Please do not reply directly to this email.</p>
        <p>&copy; {{ date('Y') }} PT Kayan LNG Nusantara - Isotank Information System</p>
    </div>

</body>
</html>
