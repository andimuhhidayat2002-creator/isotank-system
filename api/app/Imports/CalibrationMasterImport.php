<?php

namespace App\Imports;

use App\Models\MasterIsotank;
use App\Models\MasterIsotankComponent;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Carbon;

class CalibrationMasterImport
{
    public $successCount = 0;
    public $errorCount = 0;
    public $errors = [];

    public function import($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) return;
            
            // 1. Header Detection
            $header = [];
            $headerRowIndex = 0;
            
            // Try to find header row containing "isotank_number" or "iso_number" or "location"
            foreach ($rows as $i => $row) {
                $cleanRow = array_map(function($c) { 
                    return strtolower(str_replace([' ', '_', '.'], '', trim((string)$c))); 
                }, $row);
                
                if (in_array('isotanknumber', $cleanRow) || in_array('isonumber', $cleanRow) || in_array('location', $cleanRow)) {
                    $headerRowIndex = $i;
                    // Map original headers to normalized keys
                    // e.g. "PG SN" -> "pg_sn", "PG Cal Date" -> "pg_cal_date"
                    foreach ($row as $colIndex => $originalHeader) {
                        $norm = strtolower(str_replace([' ', '.'], '_', trim((string)$originalHeader)));
                        // Fix common issues: "pg_cal_date" might come as "pg_cal_date" or "pg_calibration_date"
                        $header[$colIndex] = $norm;
                    }
                    break;
                }
            }
            
            // Standard Structure Definitions
            $struct = [
                'PG' => ['Main'],
                'PSV' => [1, 2, 3, 4],
                'PRV' => [1, 2, 3, 4, 5, 6, 7]
            ];

            // 2. Process Rows
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                // Accessor Helper
                $getVal = function($keyKeywords) use ($row, $header) {
                    // keyKeywords can be string or array of strings
                    if (!is_array($keyKeywords)) $keyKeywords = [$keyKeywords];
                    
                    foreach ($header as $colIdx => $colName) {
                        foreach ($keyKeywords as $keyword) {
                            if ($colName === $keyword) {
                                return trim($row[$colIdx]);
                            }
                        }
                    }
                    return null;
                };

                try {
                    // Identify Isotank
                    $iso = $getVal(['isotank_number', 'iso_number', 'isotank']);
                    if (!$iso) continue; 

                    // Normalize ISO
                    $isoClean = preg_replace('/[^A-Za-z0-9]/', '', $iso);
                    
                    $isotank = MasterIsotank::where('iso_number', $iso)
                        ->orWhere('iso_number', $isoClean)
                        ->first();

                    if (!$isotank) {
                        // Optional: Create if not exists? User requested Master UPDATE, not create.
                        throw new \Exception("Isotank $iso not found.");
                    }

                    // Process Components
                    foreach ($struct as $type => $positions) {
                        foreach ($positions as $pos) {
                            // Construct header keys to look for
                            // e.g. Type=PG, Pos=Main => prefix="pg"
                            // Type=PSV, Pos=1 => prefix="psv1"
                            
                            $prefix = strtolower($type) . strtolower($pos === 'Main' ? '' : $pos);
                            
                            $sn = $getVal($prefix . '_sn');
                            $cert = $getVal($prefix . '_cert');
                            $press = $getVal($prefix . '_press');
                            $calDateRaw = $getVal($prefix . '_cal_date'); // "pg_cal_date"
                            $expDateRaw = $getVal($prefix . '_exp');      // "pg_exp"

                            // Skip empty component slots
                            if (!$sn && !$cert && !$calDateRaw && !$expDateRaw) continue;

                            $calDate = $this->parseDate($calDateRaw);
                            $expiry = $this->parseDate($expDateRaw);

                            // Auto-Calculate Expiry if missing but Cal Date exists
                            // Ensure strict null check or empty string check
                            if ($calDate && empty($expiry)) {
                                if ($type === 'PG') {
                                    $expiry = $calDate->copy()->addMonths(6);
                                } elseif ($type === 'PSV') {
                                    $expiry = $calDate->copy()->addYear(); // 1 Year
                                }
                                // PRV ignored
                            }

                            MasterIsotankComponent::updateOrCreate(
                                [
                                    'isotank_id' => $isotank->id,
                                    'component_type' => $type,
                                    'position_code' => (string)$pos
                                ],
                                [
                                    'serial_number' => $sn,
                                    'certificate_number' => $cert,
                                    'set_pressure' => ($type !== 'PG') ? $press : null,
                                    'last_calibration_date' => $calDate,
                                    'expiry_date' => $expiry,
                                    'description' => $this->getDescription($type, $pos),
                                    'is_active' => true
                                ]
                            );
                        }
                    }

                    $this->successCount++;

                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->errors[] = [
                        'row' => $i + 1,
                        'iso' => $iso ?? 'UNKNOWN',
                        'message' => $e->getMessage()
                    ];
                }
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getDescription($type, $pos)
    {
        switch ($type) {
            case 'PG': return 'Main Pressure Gauge';
            case 'PSV': return "Safety Relief Valve #$pos";
            case 'PRV': return "Pipeline Relief Valve #$pos";
            default: return "$type #$pos";
        }
    }

    private function parseDate($value)
    {
        if (!$value) return null;
        try {
            // Excel Numeric
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }
            // Strings (YYYY-MM-DD or DD/MM/YYYY etc)
            $d = Carbon::parse($value);
            return $d;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

