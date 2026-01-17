@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col">
            <a href="{{ route('admin.calibration-master.index') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <h2 class="fw-bold mt-2">Calibration: {{ $isotank->iso_number }}</h2>
            <span class="badge bg-light text-dark border">{{ $isotank->location ?? 'No Location' }}</span>
        </div>
        <div class="col-auto">
            
        </div>
    </div>

    @if($isotank->components->isEmpty())
        <!-- Empty State -->
        <div class="card text-center py-5 shadow-sm">
            <div class="card-body">
                <div class="display-4 text-muted mb-3"><i class="bi bi-tools"></i></div>
                <h4>No Components Configured</h4>
                <p class="text-muted">This isotank has no registered pressure gauges or safety valves.</p>
                <form action="{{ route('admin.calibration-master.init', $isotank->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-magic me-2"></i> Initialize Defaults (PG, 4 PSV, 7 PRV)
                    </button>
                    <div class="form-text mt-2">This will create empty records for standard components.</div>
                </form>
            </div>
        </div>
    @else
        <!-- Grid Editor -->
        <form action="{{ route('admin.calibration-master.update', $isotank->id) }}" method="POST">
            @csrf
            
            <div class="mb-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-save me-2"></i> Save All Changes
                </button>
            </div>

            @php
                $grouped = $isotank->components->groupBy('component_type');
                // Order: PG, PSV, PRV
                $order = ['PG', 'PSV', 'PRV'];
            @endphp

            @foreach($order as $type)
                @if(isset($grouped[$type]))
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">
                                {{ $type === 'PG' ? 'Pressure Gauge' : ($type === 'PSV' ? 'Safety Valves (SV)' : 'Relief Valves (PRV)') }}
                            </h5>
                            @if($type === 'PRV' || $type === 'PSV')
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyDown('{{ $type }}')">
                                <i class="bi bi-arrow-down-square"></i> Copy Top Row to All
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 5%">Pos</th>
                                    <th>Serial No</th>
                                    <th>Cert Number</th>
                                    @if($type !== 'PG') <th style="width: 15%">Set Pressure</th> @endif
                                    <th style="width: 15%">Cal Date</th>
                                    <th style="width: 15%">Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody class="group-{{ $type }}">
                                @foreach($grouped[$type] as $component)
                                <tr>
                                    <td class="text-center bg-light fw-bold">
                                        {{ $component->position_code }}
                                        <input type="hidden" name="components[{{ $component->id }}][id]" value="{{ $component->id }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                            name="components[{{ $component->id }}][serial_number]" 
                                            value="{{ $component->serial_number }}" placeholder="S/N...">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                            name="components[{{ $component->id }}][certificate_number]" 
                                            value="{{ $component->certificate_number }}" placeholder="Cert...">
                                    </td>
                                    @if($type !== 'PG')
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" class="form-control" 
                                                name="components[{{ $component->id }}][set_pressure]" 
                                                value="{{ $component->set_pressure }}">
                                            <span class="input-group-text">MPa</span>
                                        </div>
                                    </td>
                                    @endif
                                    <td>
                                        <input type="date" class="form-control form-control-sm cal-date" 
                                            data-type="{{ $type }}"
                                            name="components[{{ $component->id }}][last_calibration_date]" 
                                            value="{{ $component->last_calibration_date ? $component->last_calibration_date->format('Y-m-d') : '' }}">
                                    </td>
                                    <td>
                                        <input type="date" class="form-control form-control-sm exp-date" 
                                            data-type="{{ $type }}"
                                            name="components[{{ $component->id }}][expiry_date]" 
                                            value="{{ $component->expiry_date ? $component->expiry_date->format('Y-m-d') : '' }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            @endforeach

        </form>
    @endif
</div>

<script>
function copyDown(type) {
    const rows = document.querySelectorAll(`.group-${type} tr`);
    if(rows.length < 2) return;

    // Get values from first row inputs
    const firstRowInputs = rows[0].querySelectorAll('input');
    // Map to simple object by name suffix or index
    // Note: Names are unique (id based), so we can't simple clone names. We must clone VALUES.
    
    // Structure: 0=hiddenID, 1=Serial, 2=Cert, 3=Pressure(if not PG), 4=CalDate, 5=ExpDate
    // Actually using class selectors/indices is safer.

    // Let's grab values
    let sourceValues = {};
    rows[0].querySelectorAll('input').forEach((input, index) => {
        // Skip hidden id
        if(input.type === 'hidden') return;
        
        // We identify column by index in the row's cell list basically?
        // Let's use the visual column index.
        const text = input.value;
        // Store by valid key? Or just Apply by index.
    });

    // Easier approach: Iterate columns in first row, apply to subsequent rows
    const firstRowCells = rows[0].cells;
    
    // Start from loop index 1 (second row)
    for(let i=1; i < rows.length; i++) {
        const targetCells = rows[i].cells;
        
        // Iterate relevant cells (Skip Position cell 0)
        for(let c=1; c < targetCells.length; c++) {
            const sourceInput = firstRowCells[c].querySelector('input');
            const targetInput = targetCells[c].querySelector('input');
            
            if(sourceInput && targetInput) {
                // DON'T copy Serial Number (it should be unique usually). 
                // But user requested "Fill All" for Certificates and Dates.
                // Let's check the column header or name.
                // Serial Number is usually column 1. Cert is col 2.
                
                // Let's only copy Date and Cert? 
                // "One Click Setup" requested. Usually cert number is same for batch? Yes.
                // Serial number is usually DIFFERENT. 
                // Let's logic: If Serial Number column? 
                // Input name contains 'serial_number'.
                if(sourceInput.name.includes('serial_number')) continue; 
                
                targetInput.value = sourceInput.value;
            }
        }
    }
    
    alert('Copied Certificate No, Pressure, and Dates from top row to all rows in group ' + type + ' (skipped Serial Numbers).');
}
</script>
@endsection
