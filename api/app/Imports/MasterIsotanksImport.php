<?php

namespace App\Imports;

use App\Models\MasterIsotank;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterIsotanksImport
{
    public $successCount = 0;
    public $errorCount = 0;
    public $errors = [];
    public $detectedHeader = []; // To debug what we found

    public function import($file)
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            \Illuminate\Support\Facades\Log::info("Import Debug: Rows found: " . count($rows));

            \Illuminate\Support\Facades\Log::info("Import Debug: Rows found: " . count($rows));

            if (empty($rows)) {
                return;
            }

            // SMART HEADER DETECTION
            $headerRowIndex = null;
            $header = [];
            
            // Search first 10 rows for "iso_number" or "iso number"
            foreach ($rows as $index => $row) {
                $normalizedRow = array_map(function($cell) {
                    return strtolower(str_replace(' ', '_', trim((string)$cell)));
                }, $row);

                if (in_array('iso_number', $normalizedRow)) {
                    $headerRowIndex = $index;
                    $header = $normalizedRow;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                // Fallback
                $headerRowIndex = 0;
                $header = array_map(function($h) {
                    return strtolower(str_replace(' ', '_', trim((string)$h)));
                }, $rows[0]);
                $this->detectedHeader = ['fallback' => $header];
                \Illuminate\Support\Facades\Log::warning("Import Debug: Could not find 'iso_number' header. Using Row 1.");
            } else {
                $this->detectedHeader = ['found_at_row' => $headerRowIndex + 1, 'header' => $header];
                \Illuminate\Support\Facades\Log::info("Import Debug: Found header at Row " . ($headerRowIndex + 1));
            }
            
            // Re-map header to handle duplicate empty columns key issue
            // We just need the INDEX of 'iso_number' etc.
            // Actually array_combine needs unique keys. if header has empty cells, it fails or overwrites.
            
            // Better approach: Find index of critical columns
            $colMap = array_flip($header); // Key = header_name, Value = index
            
            $startRow = $headerRowIndex + 1;

            for ($i = $startRow; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row))) continue;

                try {
                    // Use index from header map to safer access
                    $getVal = function($key) use ($colMap, $row) {
                        return isset($colMap[$key]) ? ($row[$colMap[$key]] ?? null) : null;
                    };

                    $iso = $getVal('iso_number');
                    
                    if (!$iso) {
                        // Maybe user has extra rows before header that weren't caught?
                        // Or maybe footer?
                        // Just skip if ISO is empty but count as error if row is mostly full?
                        // For now, throw to catch log.
                        throw new \Exception("Missing ISO Number");
                    }
                    
                    $isotank = MasterIsotank::where('iso_number', $iso)->first();

                    $data = [
                        'iso_number' => $iso,
                         'tank_category' => $getVal('tank_category') ?? $getVal('category') ?? $getVal('type') ?? 'T75',
                        'product' => $getVal('product'),
                        'owner' => $getVal('owner'),
                        'manufacturer' => $getVal('manufacturer'),
                        'model_type' => $getVal('model_type'),
                        'manufacturer_serial_number' => $getVal('serial_number') ?? $getVal('manufacturer_serial_number') ?? $getVal('serial_no'),
                        'location' => $getVal('location'),
                        'status' => strtolower($getVal('status') ?? 'active'),
                        'initial_pressure_test_date' => $this->parseDate($getVal('initial_pressure_test') ?? $getVal('initial_pressure_test_date') ?? $getVal('init._pres._test')),
                        'csc_initial_test_date' => $this->parseDate($getVal('csc_initial_test') ?? $getVal('csc_initial_test_date') ?? $getVal('csc_initial')),
                        'class_survey_expiry_date' => $this->parseDate($getVal('class_survey_expiry') ?? $getVal('class_survey_expiry_date') ?? $getVal('class_expiry')),
                        'csc_survey_expiry_date' => $this->parseDate($getVal('csc_survey_expiry') ?? $getVal('csc_survey_expiry_date') ?? $getVal('csc_expiry')),
                    ];

                    if ($isotank) {
                        $isotank->update($data);
                    } else {
                        MasterIsotank::create($data);
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->errors[] = [
                        'row' => $i + 1,
                        'iso_number' => $iso ?? 'UNKNOWN',
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
            $value = trim($value);
            // Check if it's an Excel numeric date
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            
            // Handle "Dec-19" or "Dec-2019" text format
            // If we see partial date like "Dec-19", strtotime interprets "19" as day 19 of current year
            // We want it to be Year 19.
            if (preg_match('/^[A-Za-z]{3}-\d{2}$/', $value)) {
                $value = '01-' . $value;
            }

            // Try standard parse
            return date('Y-m-d', strtotime($value));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
