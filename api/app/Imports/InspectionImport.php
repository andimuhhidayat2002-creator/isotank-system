<?php

namespace App\Imports;

use App\Models\InspectionJob;
use App\Models\MasterIsotank;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InspectionImport
{
    protected $type;
    public $successCount = 0;
    public $errorCount = 0;
    public $errors = [];

    public function __construct($type)
    {
        $this->type = $type;
    }

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

                    $destination = null;
                    $receiverName = $rowData['receiver_name'] ?? null;
                    $fillingStatusCode = $rowData['filling_status_code'] ?? null;
                    $fillingStatusDesc = $rowData['filling_status_description'] ?? null;

                    if ($this->type === 'incoming_inspection') {
                         // Update ISOTANK directly
                         $isotank->update([
                             'location' => 'SMGRS',
                             'filling_status_code' => $fillingStatusCode,
                             'filling_status_desc' => $fillingStatusDesc
                         ]);

                    } elseif ($this->type === 'outgoing_inspection') {
                         $destination = $rowData['destination'] ?? null;
                         if (!$destination) {
                             throw new \Exception("Destination required for outgoing inspection.");
                         }
                    }

                    $plannedDate = now();
                    if (!empty($rowData['planned_date'])) {
                        // Check if it's numeric (Excel date)
                        if (is_numeric($rowData['planned_date'])) {
                            $plannedDate = Date::excelToDateTimeObject($rowData['planned_date']);
                        } else {
                            $plannedDate = date('Y-m-d', strtotime($rowData['planned_date']));
                        }
                    }

                    InspectionJob::create([
                        'isotank_id' => $isotank->id,
                        'activity_type' => $this->type,
                        'planned_date' => $plannedDate,
                        'destination' => $destination,
                        'receiver_name' => $receiverName, // Should use the column from excel
                        'filling_status_code' => $fillingStatusCode,
                        'filling_status_desc' => $fillingStatusDesc,
                        'status' => 'open'
                    ]);

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
