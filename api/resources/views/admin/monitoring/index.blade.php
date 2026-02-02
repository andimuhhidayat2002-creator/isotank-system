@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">System Activity Monitoring</h2>
            <p class="text-muted small">Real-time audit trail of user actions and media access.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.system') }}" class="btn btn-outline-danger">
                <i class="bi bi-exclamation-triangle"></i> View System Error Logs
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.monitoring.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Action / Endpoint</label>
                    <input type="text" name="action" class="form-control form-control-sm" placeholder="e.g. View Media" value="{{ request('action') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->role }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Search Description / IP</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search content..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 180px;">Timestamp</th>
                            <th style="width: 150px;">User</th>
                            <th style="width: 120px;">Action</th>
                            <th>Description</th>
                            <th style="width: 140px;">IP Address</th>
                            <th class="pe-3" style="width: 80px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold small">{{ $log->created_at->format('d M Y') }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $log->user->name ?? 'System' }}</div>
                                    <div class="badge bg-light text-dark border-0 p-0" style="font-size: 0.65rem;">
                                        {{ strtoupper($log->user->role ?? 'N/A') }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = 'bg-secondary';
                                        if(str_contains($log->action, 'POST')) $badgeClass = 'bg-success';
                                        elseif(str_contains($log->action, 'DELETE')) $badgeClass = 'bg-danger';
                                        elseif(str_contains($log->action, 'View Media')) $badgeClass = 'bg-info text-dark';
                                    @endphp
                                    <span class="badge {{ $badgeClass }} py-1 px-2" style="font-size: 0.7rem;">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-dark small">{{ $log->description }}</div>
                                </td>
                                <td>
                                    <code class="small">{{ $log->ip_address }}</code>
                                </td>
                                <td class="pe-3">
                                    @if($log->details)
                                        <button class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" data-bs-target="#details-{{ $log->id }}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @if($log->details)
                                <tr class="collapse" id="details-{{ $log->id }}">
                                    <td colspan="6" class="bg-light p-3">
                                        <pre class="mb-0 small" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->details, JSON_PRETTY_PRINT) }}</pre>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No activity logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
