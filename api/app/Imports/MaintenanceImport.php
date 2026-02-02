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
        ini_set('memory_limit', '512M');
        set_time_limit(0); // Prevent timeout
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) return;

            $header = array_shift($rows);
            $header = array_map(function($h) {
                return strtolower(str_replace(' ', '_', trim($h)));
            }, $header);

            // OPTIMIZATION: Load all isotanks into memory
            $isotankMap = MasterIsotank::pluck('id', 'iso_number')->toArray();
            $isotankStatus = MasterIsotank::pluck('status', 'iso_number')->toArray();

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;
                
                $rowData = array_combine($header, $row);

                try {
                    $iso = $rowData['iso_number'] ?? null;
                    if (!$iso) throw new \Exception("Missing ISO Number");

                    if (!isset($isotankMap[$iso])) {
                        throw new \Exception("Isotank $iso not found.");
                    }
                    if (($isotankStatus[$iso] ?? '') !== 'active') {
                        throw new \Exception("Isotank $iso is inactive.");
                    }
                    
                    $isotankId = $isotankMap[$iso];

                    // ROBUST DATE PARSING
                    $plannedDate = null;
                    if (!empty($rowData['planned_date'])) {
                        $val = trim($rowData['planned_date']);
                        if (is_numeric($val)) {
                            $plannedDate = Date::excelToDateTimeObject($val);
                        } else {
                            // Try multiple formats based on log evidence "1/18/2026" (m/d/Y)
                            $formats = ['d/m/Y', 'm/d/Y', 'd/m/y', 'm/d/y', 'Y-m-d'];
                            foreach ($formats as $format) {
                                try {
                                    $plannedDate = \Carbon\Carbon::createFromFormat($format, $val);
                                    break; // Stop if successful
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            // If still null, try generic parse
                            if (!$plannedDate) {
                                try {
                                    $plannedDate = \Carbon\Carbon::parse($val);
                                } catch (\Exception $e) {
                                    $plannedDate = now();
                                }
                            }
                        }
                    }

                    // Handle Status & Completion (for historical data)
                    $status = 'open';
                    $completedAt = null;

                    if (!empty($rowData['status'])) {
                        $rawStatus = strtolower(trim($rowData['status']));
                        if (in_array($rawStatus, ['closed', 'close', 'completed', 'done', 'finish', 'finished'])) {
                            $status = 'closed';
                        } elseif (in_array($rawStatus, ['open', 'pending', 'on progress'])) {
                            $status = 'open';
                        }
                    }

                    if ($status === 'closed') {
                        if (!empty($rowData['completion_date'])) {
                             if (is_numeric($rowData['completion_date'])) {
                                $completedAt = Date::excelToDateTimeObject($rowData['completion_date']);
                            } else {
                                try {
                                    $completedAt = \Carbon\Carbon::createFromFormat('d/m/Y', $rowData['completion_date']);
                                } catch (\Exception $e) {
                                    $completedAt = date('Y-m-d H:i:s', strtotime($rowData['completion_date']));
                                }
                            }
                        } else {
                            // If completed but no completion date, use planned date or now
                            $completedAt = $plannedDate ?? now();
                        }
                    }

                    MaintenanceJob::create([
                        'isotank_id' => $isotankId,
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
                        // FIX: Use Planned Date as created_at/reported_date for historical accuracy
                        'created_at' => $plannedDate ?? now(),
                        // FIX: Also force updated_at to match created_at so "Last Update" doesn't show today
                        'updated_at' => $plannedDate ?? now(),
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
