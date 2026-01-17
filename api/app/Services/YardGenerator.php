<?php

namespace App\Services;

class YardGenerator
{
    protected $config;

    public function __construct()
    {
        $this->config = YardLayoutConfig::getConfig();
    }

    public function generateCsv()
    {
        $headers = ['excel_row', 'excel_col', 'slot_code', 'is_active'];
        $rows = [];
        $rows[] = implode(',', $headers);
        
        $path = storage_path('app/yard_slots_raw.json');
        
        if (file_exists($path)) {
            $slots = json_decode(file_get_contents($path), true);
            foreach ($slots as $slot) {
                // Ensure data structure
                if (!isset($slot['excel_row']) || !isset($slot['excel_col']) || !isset($slot['slot_code'])) continue;
                
                $line = [
                    $slot['excel_row'],
                    $slot['excel_col'],
                    $slot['slot_code'],
                    'true' // is_active
                ];
                $rows[] = implode(',', $line);
            }
        }

        return implode("\n", $rows);
    }

    public function generateSvg($occupiedPositions = [])
    {
        // SVG Logic is DISABLED per user request.
        // Returning simple placeholder in case legacy calls exist.
        return "<svg width='100' height='100'><text x='10' y='50'>SVG Disabled</text></svg>";
    }
}
