<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; }
        .header { background-color: #0d47a1; color: white; padding: 15px; text-align: center; }
        .section { margin-bottom: 25px; }
        .section-title { border-bottom: 2px solid #0d47a1; color: #0d47a1; font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; }
        .stats-grid { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .stat-box { background: #f8f9fa; padding: 10px; width: 48%; text-align: center; border-radius: 5px; border: 1px solid #ddd; }
        .stat-number { font-size: 24px; font-weight: bold; color: #0d47a1; display: block; }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 14px; }
        th { background-color: #f1f1f1; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .status-badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; color: white; background: #666; }
        
        .alert-box { background-color: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Weekly Operations Report</h2>
            <small>{{ $date_range }}</small>
        </div>

        <div class="section">
            <h3 class="section-title">1. ACTIVITY HIGHLIGHTS (Throughput)</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-number">{{ $inspections_week }}</span>
                    <span class="stat-label">New Inspections (Week)</span>
                    <div style="font-size:10px; color:#666; margin-top:5px; line-height:1.2;">
                        <div>IN: {{ $incoming_desc }}</div>
                        <div>OUT: {{ $outgoing_desc }}</div>
                    </div>
                </div>
                <div class="stat-box">
                    <span class="stat-number">{{ $maintenance_week }}</span>
                    <span class="stat-label">Jobs Finished (Week)</span>
                    <small style="display:block; margin-top:5px; color:#888;">Total Active: {{ $maintenance_active }}</small>
                </div>
            </div>
        </div>

        <div class="section">
            <h3 class="section-title">2. ISOTANK STATUS ({{ $total_fleet }} Units Total)</h3>
            <p style="margin-top:0;">Berikut adalah sebaran status tanki saat ini:</p>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th style="text-align:right;">Count</th>
                        <th style="text-align:right;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breakdown_status as $status)
                    <tr>
                        <td>
                            @if($status['code'] == 'ready_to_fill') <span style="background:#4caf50" class="status-badge">Ready / Empty</span>
                            @elseif($status['code'] == 'filled') <span style="background:#2196f3" class="status-badge">Filled</span>
                            @elseif($status['code'] == 'under_maintenance') <span style="background:#ff9800" class="status-badge">Maintenance</span>
                            @elseif($status['code'] == 'ongoing_inspection') <span style="background:#9c27b0" class="status-badge">Inspection</span>
                            @else <span class="status-badge">{{ ucfirst(str_replace('_',' ', $status['code'])) }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;"><strong>{{ $status['count'] }}</strong></td>
                        <td style="text-align:right; color:#777;">{{ number_format(($status['count'] / $total_fleet) * 100, 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3 class="section-title">3. LOCATION OVERVIEW</h3>
            <table>
                <thead>
                    <tr>
                        <th>Location</th>
                        <th style="text-align:right;">Tank Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($breakdown_location as $loc)
                    <tr>
                        <td>{{ $loc['name'] ?: 'Unknown' }}</td>
                        <td style="text-align:right;">{{ $loc['count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($expiry_alerts_count > 0)
        <div class="alert-box">
            <h4 style="margin:0 0 10px 0; color:#d32f2f;">⚠️ COMPLIANCE WARNING</h4>
            <p style="margin:0;">There are <strong>{{ $expiry_alerts_count }}</strong> isotanks with certificates expiring in the next 30 days.</p>
            <p><small>See attached Excel for details.</small></p>
        </div>
        @else
        <div style="background:#e8f5e9; padding:15px; border-left:4px solid #4caf50; margin-bottom:20px;">
            <p style="margin:0; color:#2e7d32;">✅ All certificates are valid for the next 30 days.</p>
        </div>
        @endif
        
        <div class="footer" style="text-align: center; font-size: 12px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <p>This is an automated system message. Please do not reply directly to this email.</p>
            <p>&copy; {{ date('Y') }} PT Kayan LNG Nusantara - Isotank Information System</p>
        </div>
    </div>
</body>
</html>
