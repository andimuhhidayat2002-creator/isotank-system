<div class="table-responsive">
    <table id="{{ $tableId }}" class="table table-hover align-middle w-100">
        <thead class="table-light">
            <tr>
                <th>ISO Number</th>
                <th>Item</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Last Update</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($jobs as $job)
            <tr>
                <td class="fw-bold">
                    <a href="{{ route('admin.isotanks.show', $job->isotank_id) }}" class="text-decoration-none text-primary">
                        {{ $job->isotank->iso_number ?? '-' }}
                    </a>
                </td>
                <td class="text-uppercase small fw-bold text-muted">{{ str_replace('_', ' ', $job->source_item) }}</td>
                <td>
                    @php
                        $pClass = 'bg-light text-dark border';
                        if(in_array($job->priority, ['urgent', 'high'])) $pClass = 'bg-danger bg-opacity-10 text-danger border-danger';
                        elseif($job->priority === 'normal') $pClass = 'bg-primary bg-opacity-10 text-primary border-primary';
                        elseif($job->priority === 'low') $pClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                    @endphp
                    <span class="badge {{ $pClass }}" style="font-size: 0.7rem;">
                        {{ strtoupper($job->priority) }}
                    </span>
                </td>
                <td>
                    @php
                        $sClass = 'bg-light text-dark';
                        if($job->status === 'closed') $sClass = 'bg-success bg-opacity-10 text-success border-success';
                        elseif($job->status === 'deferred') $sClass = 'bg-secondary text-white border-0';
                        elseif($job->status === 'open') $sClass = 'bg-danger bg-opacity-10 text-danger border-danger';
                        elseif($job->status === 'on_progress') $sClass = 'bg-warning bg-opacity-10 text-dark border-warning';
                    @endphp
                    <span class="badge {{ $sClass }} rounded-pill px-3">
                        {{ strtoupper(str_replace('_', ' ', $job->status)) }}
                    </span>
                </td>
                <td class="text-muted small">
                    {{ $job->assignee->name ?? '-' }}
                </td>
                <td class="text-muted small">
                    {{ $job->updated_at->format('Y-m-d') }}
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.reports.maintenance.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">
                        View
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
