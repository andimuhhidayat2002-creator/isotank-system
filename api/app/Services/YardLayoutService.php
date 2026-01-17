<?php

namespace App\Services;

use App\Models\YardSlot;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class YardLayoutService
{
    public function importFromExcel($filePath)
    {
        Log::info("Starting Yard Layout Import from: " . $filePath);

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $maxRow = $sheet->getHighestRow();
        $maxCol = $sheet->getHighestColumn();
        $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCol);

        Log::info("Sheet dimensions: Rows {$maxRow}, Cols {$maxCol} ({$maxColIndex})");

        // Get existing slots to minimize DB queries
        // Key: row_index-col_index
        $existingSlots = YardSlot::all()->keyBy(function ($item) {
            return $item->row_index . '-' . $item->col_index;
        });

        $processedIds = [];
        $upsertData = [];

        DB::beginTransaction();

        try {
            for ($row = 1; $row <= $maxRow; $row++) {
                for ($col = 1; $col <= $maxColIndex; $col++) {
                    
                    $cell = $sheet->getCell([$col, $row]);
                    $val = $cell->getValue();
                    $text = trim((string)$val);

                    // Rule 1: Any NON-EMPTY cell = SLOT
                    // Rule 3: Empty cell = NO SLOT
                    if ($text === '') {
                        continue;
                    }

// Rule 2: Cell text (trimmed, uppercase) = area_label
                    $areaLabel = strtoupper($text);

                    // Capture Background Color
                    $style = $cell->getStyle();
                    $bgColor = null;
                    if ($style->getFill()->getFillType() === \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID) {
                        $startColor = $style->getFill()->getStartColor();
                         if ($startColor->getRGB() !== 'FFFFFF') {
                            $bgColor = '#' . $startColor->getRGB();
                        }
                    }

                    $key = $row . '-' . $col;

                    if ($existingSlots->has($key)) {
                        // Update existing
                        $slot = $existingSlots->get($key);
                        
                        // Rule 3b: If slot exists, Update area_label & bg_color ONLY (and ensure active)
                        // We check if change is needed to save performance
                        if ($slot->area_label !== $areaLabel || $slot->bg_color !== $bgColor || !$slot->is_active) {
                            $slot->area_label = $areaLabel;
                            $slot->bg_color = $bgColor;
                            $slot->is_active = true;
                            $slot->save();
                        }
                        $processedIds[] = $slot->id;
                    } else {
                        // Rule 4: If slot does not exist, Insert new slot
                        $slot = YardSlot::create([
                            'row_index' => $row,
                            'col_index' => $col,
                            'area_label' => $areaLabel,
                            'bg_color' => $bgColor,
                            'is_active' => true
                        ]);
                        $processedIds[] = $slot->id;
                    }
                }
            }

            // Rule 5: If slot exists in DB but missing in Excel -> Mark is_active = false. DO NOT DELETE.
            // We do this by checking which IDs were NOT processed
            YardSlot::whereNotIn('id', $processedIds)->update(['is_active' => false]);
            
            DB::commit();
            Log::info("Yard Layout Import Completed. Processed " . count($processedIds) . " slots.");

            return [
                'processed' => count($processedIds),
                'total_rows' => $maxRow
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Yard Layout Import Failed: " . $e->getMessage());
            throw $e;
        }
    }
}
