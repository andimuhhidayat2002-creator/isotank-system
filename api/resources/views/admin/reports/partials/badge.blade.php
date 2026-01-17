@if($status === 'good' || $status === '✓' || $status === 'closed')
    <span class="badge bg-success">GOOD</span>
@elseif($status === 'damaged' || $status === '✗' || $status === 'open')
    <span class="badge bg-danger">DAMAGED</span>
@elseif($status === 'missing' || $status === '?')
    <span class="badge bg-warning text-dark">MISSING</span>
@else
    <span class="badge bg-secondary">{{ strtoupper($status ?? 'N/A') }}</span>
@endif
