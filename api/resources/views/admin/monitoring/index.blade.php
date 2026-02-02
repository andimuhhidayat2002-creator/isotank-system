@extends('layouts.app')

@section('content')
<style>
    /* Premium SOC Theme Colors */
    :root {
        --soc-bg: #12141a;
        --soc-card: #1c1f26;
        --soc-border: #2d323e;
        --soc-text: #e2e4e9;
        --soc-muted: #9499a6;
        --soc-primary: #3b82f6;
        --soc-success: #10b981;
        --soc-warning: #f59e0b;
        --soc-danger: #ef4444;
        --soc-info: #06b6d4;
    }

    .monitoring-view {
        background-color: var(--soc-bg);
        color: var(--soc-text);
        margin: -24px; /* Offset app.blade.php padding */
        padding: 24px;
        min-height: 100vh;
    }

    .metric-card {
        background: var(--soc-card);
        border: 1px solid var(--soc-border);
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.2s;
    }
    .metric-card:hover { transform: translateY(-3px); }

    .metric-icon {
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 12px;
    }

    .status-badge {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .log-table {
        background: var(--soc-card);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--soc-border);
    }
    .log-table thead {
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid var(--soc-border);
    }
    .log-table th { color: var(--soc-muted); font-size: 0.75rem; border: none; padding: 15px; }
    .log-table td { border-bottom: 1px solid rgba(255,255,255,0.03); padding: 15px; vertical-align: middle; color: var(--soc-text); }
    .log-table tr:hover { background: rgba(255,255,255,0.01); }

    .filter-section {
        background: var(--soc-card);
        border: 1px solid var(--soc-border);
        border-radius: 12px;
        padding: 20px;
    }
    .form-control-soc, .form-select-soc {
        background: var(--soc-bg) !important;
        border: 1px solid var(--soc-border) !important;
        color: var(--soc-text) !important;
        border-radius: 8px;
    }
    .form-control-soc::placeholder { color: #555; }

    .chart-mini { height: 40px; }
    
    .text-primary-soc { color: var(--soc-primary); }
    .text-success-soc { color: var(--soc-success); }
    .text-warning-soc { color: var(--soc-warning); }
</style>

<div class="monitoring-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Server Activity SOC</h2>
            <div class="small text-muted d-flex align-items-center">
                <span class="text-success me-2"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> LIVE</span>
                Real-time monitoring and security audit trail.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.system') }}" class="btn btn-dark border-secondary">
                <i class="bi bi-terminal me-2"></i> System Logs
            </a>
            <button class="btn btn-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- CPU -->
        <div class="col-md-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="small text-muted">CPU Usage</div>
                        <h3 class="fw-bold mb-0 text-primary-soc">{{ $metrics['cpu'] }}%</h3>
                    </div>
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cpu fs-4"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                    <div class="progress-bar bg-primary" style="width: {{ $metrics['cpu'] }}%"></div>
                </div>
                <div class="small text-muted mt-2">Status: Scalable</div>
            </div>
        </div>
        <!-- RAM -->
        <div class="col-md-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="small text-muted">Memory Usage</div>
                        <h3 class="fw-bold mb-0 text-success-soc">{{ $metrics['ram']['used'] }}</h3>
                    </div>
                    <div class="metric-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-memory fs-4"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                    <div class="progress-bar bg-success" style="width: {{ $metrics['ram']['percent'] }}%"></div>
                </div>
                <div class="small text-muted mt-2">Total: {{ $metrics['ram']['total'] }}</div>
            </div>
        </div>
        <!-- Disk -->
        <div class="col-md-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="small text-muted">Disk Usage</div>
                        <h3 class="fw-bold mb-0 text-info">{{ $metrics['disk']['percent'] }}%</h3>
                    </div>
                    <div class="metric-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-hdd fs-4"></i>
                    </div>
                </div>
                <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                    <div class="progress-bar bg-info" style="width: {{ $metrics['disk']['percent'] }}%"></div>
                </div>
                <div class="small text-muted mt-2">{{ $metrics['disk']['used'] }} used / {{ $metrics['disk']['total'] }}</div>
            </div>
        </div>
        <!-- Users -->
        <div class="col-md-3">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="small text-muted">Active Users</div>
                        <div class="d-flex align-items-end gap-2">
                           <h3 class="fw-bold mb-0 text-warning-soc">{{ $metrics['active_users'] }}</h3>
                           <span class="small text-muted mb-1">ONLINE</span>
                        </div>
                    </div>
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-1">
                    @for($i=0; $i<8; $i++)
                        <div style="flex:1; height:4px; background: {{ $i < ($metrics['active_users'] * 2) ? 'var(--soc-warning)' : 'rgba(255,255,255,0.05)' }}; border-radius: 2px;"></div>
                    @endfor
                </div>
                <div class="small text-muted mt-2">Active in last 15m</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Column -->
        <div class="col-lg-9">
            <div class="log-table shadow-lg">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 180px;">TIMESTAMP</th>
                                <th style="width: 160px;">USER</th>
                                <th>ACTION / DESCRIPTION</th>
                                <th style="width: 140px;">IP ADDRESS</th>
                                <th style="width: 100px;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <div class="fw-bold small">{{ $log->created_at->format('Y-m-d') }}</div>
                                        <div class="small text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-opacity-20 text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                                                {{ substr($log->user->name ?? 'S', 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold small">{{ $log->user->name ?? 'System' }}</div>
                                                <div class="text-muted" style="font-size: 0.65rem;">{{ strtoupper($log->user->role ?? 'N/A') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-primary-soc mb-1">{{ $log->action }}</div>
                                        <div class="small text-muted">{{ $log->description }}</div>
                                    </td>
                                    <td>
                                        <code class="small text-muted bg-dark px-1 rounded">{{ $log->ip_address }}</code>
                                    </td>
                                    <td>
                                        <span class="status-badge bg-success bg-opacity-10 text-success border border-success border-opacity-20">
                                            SUCCESS
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No activity logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="p-3 border-top border-secondary border-opacity-10">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar / Filters -->
        <div class="col-lg-3">
            <div class="filter-section">
                <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i> Advanced Filters</h6>
                <form action="{{ route('admin.monitoring.index') }}" method="GET">
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Search Keywords</label>
                        <input type="text" name="search" class="form-control form-control-soc form-control-sm" placeholder="Description, IP..." value="{{ request('search') }}">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">User Account</label>
                        <select name="user_id" class="form-select form-select-soc form-select-sm">
                            <option value="">All Accounts</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="small text-muted mb-1">Quick Actions</label>
                        <div class="d-grid gap-1">
                            <button type="submit" name="action" value="POST" class="btn btn-outline-secondary btn-sm text-start py-1 px-2 border-opacity-10" style="font-size: 0.75rem;">
                                <i class="bi bi-plus-square me-2"></i> Write Operations
                            </button>
                            <button type="submit" name="action" value="View Media" class="btn btn-outline-secondary btn-sm text-start py-1 px-2 border-opacity-10" style="font-size: 0.75rem;">
                                <i class="bi bi-image me-2"></i> Media Access
                            </button>
                        </div>
                    </div>
                    <hr class="border-secondary border-opacity-20">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                        <a href="{{ route('admin.monitoring.index') }}" class="btn btn-dark btn-sm">Reset All</a>
                    </div>
                </form>
            </div>

            <div class="mt-4 p-3 rounded bg-dark border border-secondary border-opacity-10">
                <h6 class="small fw-bold mb-2">Security Status</h6>
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-shield-lock-fill text-success me-2"></i>
                    <span class="small">SSL: Active (Certbot)</span>
                </div>
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-incognito text-info me-2"></i>
                    <span class="small">Privacy: Search Disabled</span>
                </div>
                 <div class="d-flex align-items-center">
                    <i class="bi bi-hdd-network text-warning me-2"></i>
                    <span class="small">IP: Hidden behind DNS</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
