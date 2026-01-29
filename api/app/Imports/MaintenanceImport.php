<?php

namespace App\Imports;

use App\Models\MaintenanceJob;
use App\Models\MasterIsotank;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MaintenanceImport
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

                    // Handle Status & Completion (for historical data)
                    $status = 'open';
                    $completedAt = null;

                    if (!empty($rowData['status'])) {
                        $rawStatus = strtolower(trim($rowData['status']));
                        if (in_array($rawStatus, ['completed', 'close', 'closed', 'done', 'finish', 'finished'])) {
                            $status = 'completed';
                        } elseif (in_array($rawStatus, ['open', 'pending'])) {
                            $status = 'open';
                        }
                    }

                    if ($status === 'completed') {
                        if (!empty($rowData['completion_date'])) {
                             if (is_numeric($rowData['completion_date'])) {
                                $completedAt = Date::excelToDateTimeObject($rowData['completion_date']);
                            } else {
                                $completedAt = date('Y-m-d H:i:s', strtotime($rowData['completion_date']));
                            }
                        } else {
                            // If completed but no completion date, use planned date or now
                            $completedAt = $plannedDate ?? now();
                        }
                    }

                    MaintenanceJob::create([
                        'isotank_id' => $isotank->id,
                        'source_item' => $rowData['item_name'] ?? 'General',
                        'description' => $rowData['description'] ?? 'Bulk uploaded maintenance job',
                        'work_description' => $rowData['work_description'] ?? null,
                        'priority' => $rowData['priority'] ?? 'normal',
                        'status' => $status,
                        'planned_date' => $plannedDate,
                        'completed_at' => $completedAt,
                        'part_damage' => $rowData['part_damage'] ?? null,
                        'damage_type' => $rowData['damage_type'] ?? null,
                        'location' => $rowData['location'] ?? null,
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
