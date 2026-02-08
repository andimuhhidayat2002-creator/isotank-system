@component('mail::message')
# High Vacuum Alert & New Maintenance

An Isotank has been flagged with a high vacuum reading (> 8 mTorr) and requires maintenance.

**Isotank:** {{ $isotank->iso_number }}
**Vacuum Reading:** {{ $vacuumValue }} mTorr
**Maintenance Item:** {{ $job->item_name }}
**Issue:** {{ $job->issue }}
**Location:** {{ $isotank->location ?? 'Unknown' }}

@component('mail::button', ['url' => route('admin.dashboard.maintenance')])
View Maintenance Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
