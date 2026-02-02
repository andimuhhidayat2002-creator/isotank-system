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

    private function normalizeIso($iso)
    {
        // Remove all non-alphanumeric characters except hyphen
        // This handles spaces, tabs, newlines, and pesky Non-Breaking Spaces (NBSP)
        return strtoupper(preg_replace('/[^a-zA-Z0-9-]/', '', $iso));
    }

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

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;
                
                // FIX: Ensure row has same number of columns as header
                $headerCount = count($header);
                $rowCount = count($row);
                
                if ($rowCount < $headerCount) {
                    // Pad with nulls if row is shorter
                    $row = array_pad($row, $headerCount, null);
                } elseif ($rowCount > $headerCount) {
                    // Trim if row is longer
                    $row = array_slice($row, 0, $headerCount);
                }
                
                $rowData = array_combine($header, $row);
                if ($rowData === false) {
                    \Log::error("Row " . ($index + 2) . " skipped: array_combine failed");
                    continue;
                }

                try {
                    $rawIso = $rowData['iso_number'] ?? null;
                    if (!$rawIso) throw new \Exception("Missing ISO Number");
                    
                    // NUCLEAR CLEANING: Remove ANYTHING that is not a letter, number, or hyphen.
                    // This kills Zero-Width spaces, NBSP, BOMs, tabs, newlines, etc.
                    $cleanIso = strtoupper(preg_replace('/[^a-zA-Z0-9-]/', '', $rawIso));

                    if ($index === 0) {
                        \Log::info("DEBUG IMPORT: Raw ISO: [" . bin2hex($rawIso) . "] Clean ISO: [" . $cleanIso . "]");
                    }

                    // DATABASE LOOKUP using the Clean ISO
                    $isotank = MasterIsotank::where('iso_number', $cleanIso)->first();
                    
                    if (!$isotank) {
                         // Fallback: Try fuzzy search just in case
                         $isotank = MasterIsotank::where('iso_number', 'LIKE', $cleanIso)->first();
                    }

                    if (!$isotank) {
                        throw new \Exception("Isotank '$cleanIso' (Raw: '$rawIso') not found in database.");
                    }
                    
                    if ($isotank->status !== 'active') {
                        throw new \Exception("Isotank '$cleanIso' is inactive.");
                    }
                    
                    $isotankId = $isotank->id;

                    // ROBUST DATE PARSING
                    $plannedDate = null;
                    if (!empty($rowData['planned_date'])) {
                        $val = trim($rowData['planned_date']);
                        if (is_numeric($val)) {
                            $plannedDate = Date::excelToDateTimeObject($val);
                        } else {
                            // Try multiple formats with STRICT overflow checking
                            $formats = ['d/m/Y', 'm/d/Y', 'd/m/y', 'm/d/y', 'Y-m-d', 'Y/m/d'];
                            foreach ($formats as $format) {
                                $d = \DateTime::createFromFormat($format, $val);
                                $errors = \DateTime::getLastErrors();
                                
                                // Check for errors OR warnings (warnings catch overflows like Month 27)
                                if ($d && $errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                                    $plannedDate = \Carbon\Carbon::instance($d);
                                    break; 
                                }
                            }
                            
                            // If still null, try generic parse as last resort (with Carbon's best guess)
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
                    // LOG ERROR FOR DEBUGGING
                    \Log::error("Maintenance Import Row " . ($index + 2) . " Failed: " . $e->getMessage());
                    
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
