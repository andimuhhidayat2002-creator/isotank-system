<div class="table-responsive">
    <table id="{{ $tableId }}" class="table table-hover align-middle w-100" data-order='[[ 8, "desc" ]]'>
        <thead class="table-light">
            <tr>
                <th>ISO Number</th>
                <th>Item</th>
                <th>Part Damage</th>
                <th>Type</th>
                <th>Loc</th>
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
                <td class="text-uppercase small fw-bold text-white">{{ str_replace('_', ' ', $job->source_item) }}</td>
                <td class="small text-white">{{ $job->part_damage ?? '-' }}</td>
                <td class="small text-white">{{ $job->damage_type ?? '-' }}</td>
                <td class="small text-white">{{ $job->location ?? '-' }}</td>
                <td>
                    @php
                        $pClass = 'bg-light text-white border';
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
                        $sClass = 'bg-light text-white';
                        if($job->status === 'closed') $sClass = 'bg-success bg-opacity-10 text-success border-success';
                        elseif($job->status === 'deferred') $sClass = 'bg-secondary text-white border-0';
                        elseif($job->status === 'open') $sClass = 'bg-danger bg-opacity-10 text-danger border-danger';
                        elseif($job->status === 'on_progress') $sClass = 'bg-warning text-white border-warning';
                    @endphp
                    <span class="badge {{ $sClass }} rounded-pill px-3">
                        {{ strtoupper(str_replace('_', ' ', $job->status)) }}
                    </span>
                </td>
                <td class="text-white small">
                    {{ $job->assignee->name ?? '-' }}
                </td>
                <td class="text-white small">
                    {{ $job->updated_at->format('Y-m-d H:i') }}
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
