<?php

namespace App\Exports;

use App\Models\MasterIsotankComponent;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CalibrationAlertsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function query()
    {
        return MasterIsotankComponent::query()
            ->with(['isotank:id,iso_number,location'])
            ->where('expiry_date', '<', now()->addMonths(3))
            ->orderBy('expiry_date', 'asc');
    }

    public function headings(): array
    {
        return [
            'Expiry Date',
            'Days Left',
            'Status',
            'Isotank Number',
            'Location',
            'Component Type',
            'Position',
            'Serial Number'
        ];
    }

    public function map($component): array
    {
        $daysLeft = $component->expiry_date ? now()->diffInDays($component->expiry_date, false) : 0;
        $status = 'Valid';
        if ($daysLeft < 0) $status = 'Expired';
        elseif ($daysLeft < 30) $status = 'Critical';
        elseif ($daysLeft < 60) $status = 'Warning';

        return [
            $component->expiry_date ? $component->expiry_date->format('Y-m-d') : 'N/A',
            (int)$daysLeft,
            $status,
            optional($component->isotank)->iso_number ?? 'Unknown',
            optional($component->isotank)->location ?? '-',
            $component->component_type,
            $component->position_code ?? '-',
            $component->serial_number ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
