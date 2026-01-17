<?php

namespace App\Imports;

use App\Models\VacuumLog;
use App\Models\MasterIsotank;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class VacuumImport
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
            
            // 1. SMART HEADER DETECTION (With Aliases)
            $headerRowIndex = null;
            $header = [];
            
            // Allowable aliases for 'iso_number'
            $isoAliases = ['iso_number', 'isonumber', 'iso_no', 'isono', 'unit_number', 'unit_no', 'container_no', 'tank_no'];

            foreach ($rows as $index => $row) {
                // Normalize row to lower_snake_case
                $normalizedRow = array_map(function($cell) {
                    return strtolower(str_replace([' ', '-', '.'], '_', trim((string)$cell)));
                }, $row);

                // Check if any alias exists in this row
                if (!empty(array_intersect($isoAliases, $normalizedRow))) {
                    $headerRowIndex = $index;
                    $header = $normalizedRow;
                    break;
                }
            }

            // Fallback to row 0 if detection fails
            if ($headerRowIndex === null) {
                $headerRowIndex = 0;
                $header = array_map(function($h) {
                    return strtolower(str_replace([' ', '-', '.'], '_', trim((string)$h)));
                }, $rows[0]);
            }
            
            $colMap = array_flip($header); 
            $startRow = $headerRowIndex + 1;

            // Definition of possible column names for data extraction
            $vacuumAliases = ['vacuum_value', 'vacuum', 'reading', 'value', 'mtorr', 'vacuum_reading'];
            $tempAliases = ['temperature', 'temp', 'deg_c', 'temp_c', 'vacuum_temperature'];
            $dateAliases = ['check_date', 'date', 'checked_at', 'reading_date', 'time'];
            $remarksAliases = ['remarks', 'remark', 'comment', 'notes'];

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                try {
                    // Helper to fuzzy find value from multiple aliases
                    $getVal = function($aliases) use ($colMap, $row) {
                        if (!is_array($aliases)) $aliases = [$aliases];
                        foreach ($aliases as $alias) {
                            if (isset($colMap[$alias])) return $row[$colMap[$alias]];
                        }
                        return null;
                    };

                    // ISO Lookup (Try multiple keys)
                    $isoRaw = $getVal($isoAliases);
                    if (!$isoRaw) throw new \Exception("Row " . ($i+1) . ": Missing ISO Number in columns: " . implode(', ', $isoAliases));

                    // Normalize ISO for Lookup (remove spaces, dashes)
                    $isoClean = preg_replace('/[^A-Za-z0-9]/', '', $isoRaw);

                    // Try Exact Match OR Clean Match
                    $isotank = MasterIsotank::where('iso_number', $isoRaw)
                        ->orWhere('iso_number', $isoClean)
                        ->first();

                    if (!$isotank) {
                        // Try adding wildcards if strictly alphanumeric
                        $isotank = MasterIsotank::where('iso_number', 'LIKE', "%$isoClean%")->first();
                    }

                    if (!$isotank) {
                        throw new \Exception("Isotank '$isoRaw' not found in system.");
                    }
                    
                    // Date Parsing
                    $dateRaw = $getVal($dateAliases);
                    $checkDate = $this->parseDate($dateRaw);
                    if (!$checkDate) $checkDate = now(); 

                    // Value Parsing
                    $val = $getVal($vacuumAliases) ?? 0;
                    
                    VacuumLog::create([
                        'isotank_id' => $isotank->id,
                        'vacuum_value_raw' => $val,
                        'vacuum_unit_raw' => $getVal(['unit', 'uom']) ?? 'mTorr',
                        'vacuum_value_mtorr' => (float)$val,
                        'temperature' => $getVal($tempAliases) ?? null,
                        'check_datetime' => $checkDate
                    ]);

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->errors[] = [
                        'row' => $i + 1,
                        'iso_number' => $isoRaw ?? 'UNKNOWN',
                        'error' => $e->getMessage()
                    ];
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function parseDate($value)
    {
        if (!$value) return null;
        try {
            $value = trim((string)$value);
            // Check if it's an Excel numeric date
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
            }
            // Handle "Dec-19" or "Dec-2019" text format
            if (preg_match('/^[A-Za-z]{3}-\d{2}$/', $value)) {
                $value = '01-' . $value;
            }
            // Try standard parse
            return date('Y-m-d H:i:s', strtotime($value));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
