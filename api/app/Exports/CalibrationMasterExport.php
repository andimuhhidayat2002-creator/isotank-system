<?php

namespace App\Exports;

use App\Models\MasterIsotank;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CalibrationMasterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $struct = [
        'PG' => ['Main'],
        'PSV' => [1, 2, 3, 4],
        'PRV' => [1, 2, 3, 4, 5, 6, 7]
    ];

    public function collection()
    {
        return MasterIsotank::with('components')
                    ->select('id', 'iso_number', 'location')
                    ->get();
    }

    public function headings(): array
    {
        $headers = ['Isotank Number', 'Location'];

        foreach ($this->struct as $type => $positions) {
            foreach ($positions as $pos) {
                $p = $type . ($pos === 'Main' ? '' : $pos);
                $headers[] = "$p SN";
                $headers[] = "$p Cert";
                if ($type !== 'PG') $headers[] = "$p Press";
                $headers[] = "$p Exp";
            }
        }

        return $headers;
    }

    public function map($isotank): array
    {
        $row = [
            $isotank->iso_number,
            $isotank->location
        ];

        // Map components
        $comps = [];
        foreach ($isotank->components as $c) {
            $key = $c->component_type . '_' . $c->position_code;
            $comps[$key] = $c;
        }

        foreach ($this->struct as $type => $positions) {
            foreach ($positions as $pos) {
                $key = $type . '_' . $pos;
                $c = $comps[$key] ?? null;

                $row[] = $c ? $c->serial_number : '';
                $row[] = $c ? $c->certificate_number : '';
                if ($type !== 'PG') $row[] = $c ? $c->set_pressure : '';
                $row[] = ($c && $c->expiry_date) ? $c->expiry_date->format('Y-m-d') : '';
            }
        }

        return $row;
    }
}
