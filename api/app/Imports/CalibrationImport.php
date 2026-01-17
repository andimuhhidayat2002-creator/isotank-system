<?php

namespace App\Imports;

use App\Models\CalibrationLog;
use App\Models\MasterIsotank;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CalibrationImport
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

            $header = array_shift($rows);
            $header = array_map(function($h) {
                return strtolower(str_replace(' ', '_', trim($h)));
            }, $header);

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;
                $rowData = array_combine($header, $row);

                try {
                    $iso = $rowData['iso_number'] ?? null;
                    if (!$iso) throw new \Exception("Missing ISO Number");

                    $isotank = MasterIsotank::where('iso_number', $iso)->first();

                    if (!$isotank) {
                        throw new \Exception("Isotank $iso not found.");
                    }
                    if ($isotank->status !== 'active') {
                        throw new \Exception("Isotank $iso is inactive.");
                    }

                    $plannedDate = null;
                    if (!empty($rowData['planned_date'])) {
                        if (is_numeric($rowData['planned_date'])) {
                            $plannedDate = Date::excelToDateTimeObject($rowData['planned_date']);
                        } else {
                            $plannedDate = date('Y-m-d', strtotime($rowData['planned_date']));
                        }
                    }

                    // Define the columns that represent items and their serial numbers
                    // Column header in excel -> Item Name in DB
                    $itemColumns = [
                        'sn_pressure_gauge' => 'Pressure Gauge',
                        'sn_psv_1' => 'PSV 1',
                        'sn_psv_2' => 'PSV 2',
                        'sn_psv_3' => 'PSV 3',
                        'sn_psv_4' => 'PSV 4'
                    ];

                    $itemsCreated = 0;

                    foreach ($itemColumns as $colKey => $itemName) {
                        // Check if the SN column has a value
                        if (!empty($rowData[$colKey])) {
                            CalibrationLog::create([
                                'isotank_id' => $isotank->id,
                                'item_name' => $itemName,
                                'serial_number' => $rowData[$colKey],
                                'description' => $rowData['description'] ?? 'Calibration Request',
                                'planned_date' => $plannedDate,
                                'vendor' => $rowData['vendor'] ?? null,
                                'status' => 'planned',
                                'created_by' => auth()->id(),
                            ]);
                            $itemsCreated++;
                        }
                    }

                    if ($itemsCreated == 0) {
                        // If no specific item SNs (legacy or just generic), maybe fallback or skip?
                        // Let's assume user MUST provide at least one SN to schedule calibration.
                        throw new \Exception("No serial numbers provided for any known items (sn_pressure_gauge, sn_psv_1, etc.)");
                    }

                    // We count successful item creations
                    // $this->successCount += $itemsCreated; 
                    // To be consistent with other imports which count ROWS, let's count rows. 
                    // But if 1 row creates 5 jobs, user might want to know 5 jobs were created.
                    // For now, let's increment successCount by 1 (per row success) to avoid confusion with row processing.
                    $this->successCount++;

                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->errors[] = [
                        'row' => $index + 2,
                        'iso_number' => $rowData['iso_number'] ?? 'UNKNOWN',
                        'error' => $e->getMessage()
                    ];
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
