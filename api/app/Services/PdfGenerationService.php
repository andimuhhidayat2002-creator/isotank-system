<?php

namespace App\Services;

use App\Models\InspectionLog;
use App\Models\MaintenanceJob;
use App\Models\ReceiverConfirmation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * PDF GENERATION SERVICE
 * 
 * CRITICAL RULES:
 * - PDF is READ-ONLY and generated from DATABASE LOGS
 * - PDF MUST NEVER read from UI state, drafts, or temporary cache
 * - Source of truth: inspection_logs, receiver_confirmations, master_isotanks, etc.
 * - Auto-generate on inspection submission
 */
class PdfGenerationService
{
    /**
     * Generate PDF for incoming inspection
     * 
     * @param InspectionLog $inspectionLog
     * @return string PDF path
     */
    public function generateIncomingPdf(InspectionLog $inspectionLog): string
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        
        // Load relationships
        $inspectionLog->load(['isotank', 'inspector', 'inspectionJob']);
        
        // Get open maintenance items for this isotank
        $openMaintenance = MaintenanceJob::where('isotank_id', $inspectionLog->isotank_id)
            ->where('status', 'open')
            ->with('triggeredByInspection')
            ->get();
        
        // Prepare T75 Data (Fix for Missing Data & Units)
        $t75Data = $this->prepareT75Data($inspectionLog);
            
        // Prepare data
        $data = [
            'type' => 'incoming',
            'inspection' => $inspectionLog,
            'isotank' => $inspectionLog->isotank,
            'inspector' => $inspectionLog->inspector,
            'job' => $inspectionLog->inspectionJob,
            'openMaintenance' => $openMaintenance,
            't75Data' => $t75Data, // INJECTED
            'generatedAt' => now(),
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.inspection_report', $data);
        
        // Store PDF
        $filename = 'inspection_' . $inspectionLog->id . '_' . time() . '.pdf';
        $path = 'inspection_pdfs/' . $filename;
        
        Storage::disk('local')->put($path, $pdf->output());
        
        // Update inspection log with PDF path
        $inspectionLog->update(['pdf_path' => $path]);
        
        return $path;
    }
    
    /**
     * Generate PDF for outgoing inspection
     * 
     * @param InspectionLog $inspectionLog
     * @return string PDF path
     */
    public function generateOutgoingPdf(InspectionLog $inspectionLog): string
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        // Ensure we have the latest data (including signature path updated in controller)
        $inspectionLog->refresh();
        
        // Load relationships
        $inspectionLog->load(['isotank', 'inspector', 'inspectionJob']);
        
        // Get receiver confirmations
        $receiverConfirmations = ReceiverConfirmation::where('inspection_log_id', $inspectionLog->id)
            ->get()
            ->keyBy('item_name');
        
        // Get open maintenance items for this isotank
        $openMaintenance = MaintenanceJob::where('isotank_id', $inspectionLog->isotank_id)
            ->where('status', 'open')
            ->with('triggeredByInspection')
            ->get();
        
        // Check if all items are accepted
        $allAccepted = $receiverConfirmations->every(function ($confirmation) {
            return $confirmation->receiver_decision === 'ACCEPT';
        });

        // Prepare T75 Data (Fix for Missing Data & Units)
        $t75Data = $this->prepareT75Data($inspectionLog);
        
        // Prepare data
        $data = [
            'type' => 'outgoing',
            'inspection' => $inspectionLog,
            'isotank' => $inspectionLog->isotank,
            'inspector' => $inspectionLog->inspector,
            'job' => $inspectionLog->inspectionJob,
            'receiverConfirmations' => $receiverConfirmations,
            'openMaintenance' => $openMaintenance,
            'allAccepted' => $allAccepted,
            't75Data' => $t75Data, // INJECTED
            'generatedAt' => now(),
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.inspection_report', $data);
        
        // Store PDF
        $filename = 'inspection_outgoing_' . $inspectionLog->id . '_' . time() . '.pdf';
        $path = 'inspection_pdfs/' . $filename;
        
        Storage::disk('local')->put($path, $pdf->output());
        
        // Update inspection log with PDF path
        $inspectionLog->update(['pdf_path' => $path]);
        
        return $path;
    }

    /**
     * Helper to prepare T75 specific data structure for PDF view
     * Ensures units are correct and all fields exist to prevent blade errors
     */
    private function prepareT75Data($inspection) {
        $data = [];
        $i = $inspection;
        // Parsing data helper (json vs column)
        $json = is_string($i->inspection_data) ? json_decode($i->inspection_data, true) : $i->inspection_data;
        if(!is_array($json)) $json = [];
        
        $get = function($k) use ($i, $json) {
            return $i->$k ?? $json[$k] ?? null;
        };

        // Helper timestamp cleaner
        $time = function($k) use ($get) {
            $val = $get($k);
            if (!$val) return '';
            // Return only time part (11:00) if full datetime
            if(strlen($val) > 10) return '(' . substr($val, 11, 5) . ')';
            return '';
        };

        // IBOX SYSTEM
        $data['ibox'] = [
            'condition' => $get('ibox_condition'),
            'battery' => $get('ibox_battery_percent') ? $get('ibox_battery_percent').' %' : '-',
            'pressure' => $get('ibox_pressure') ? $get('ibox_pressure').' MPa' : '-',
            // FIX: Incoming uses 'ibox_temperature', Outgoing uses 'ibox_temperature_1'
            'temp1' => ($temp1Val = $get('ibox_temperature_1') ?: $get('ibox_temperature')) ? $temp1Val.' °C' : '-',
            'temp1_time' => $time('ibox_temperature_1_timestamp'),
            'temp2' => $get('ibox_temperature_2') ? $get('ibox_temperature_2').' °C' : '-',
            'temp2_time' => $time('ibox_temperature_2_timestamp'),
            'level' => $get('ibox_level') ? $get('ibox_level').' %' : '-',
        ];

        // VACUUM SYSTEM
        $data['vacuum'] = [
            'gauge_condition' => $get('vacuum_gauge_condition'),
            'port_condition' => $get('vacuum_port_suction_condition') ?? $get('port_suction_condition') ?? $get('Port Suction Condition') ?? $get('Port_Suction_Condition'),
            'value' => $get('vacuum_value') ? (float)$get('vacuum_value') . ' ' . ($get('vacuum_unit') ?? 'mTorr') : '-',
            'temp' => $get('vacuum_temperature') ? $get('vacuum_temperature').' °C' : '-',
            'check_date' => $get('vacuum_check_datetime') ? substr($get('vacuum_check_datetime'),0,16) : '-',
        ];
        
        // INSTRUMENTS
        $data['instruments'] = [
            'pressure_gauge' => [
                'condition' => $get('pressure_gauge_condition'),
                'sn' => $get('pressure_gauge_serial_number') ?? '-',
                'cal_date' => $get('pressure_gauge_calibration_date') ?? '-',
                'valid' => $get('pressure_gauge_valid_until') ?? '-',
                'p1' => $get('pressure_1') ? $get('pressure_1').' MPa' : '-',
                'p1_time' => $time('pressure_1_timestamp'),
                'p2' => $get('pressure_2') ? $get('pressure_2').' MPa' : '-',
                'p2_time' => $time('pressure_2_timestamp'),
            ],
            'level_gauge' => [
                'condition' => $get('level_gauge_condition'),
                'l1' => $get('level_1') ? $get('level_1').' mmH2O' : '-',
                'l1_time' => $time('level_1_timestamp'),
                'l2' => $get('level_2') ? $get('level_2').' mmH2O' : '-',
                'l2_time' => $time('level_2_timestamp'),
            ]
        ];

        // PSV
        $psv = [];
        for($p=1; $p<=4; $p++) {
            $psv[] = [
                'label' => "PSV #$p",
                'condition' => $get("psv{$p}_condition"),
                'status' => $get("psv{$p}_status") ? strtoupper($get("psv{$p}_status")) : '-',
                'sn' => $get("psv{$p}_serial_number") ?? '-',
                'cal_date' => $get("psv{$p}_calibration_date") ?? '-',
                'valid_until' => $get("psv{$p}_valid_until") ?? '-',
            ];
        }
        $data['psv'] = $psv;
        
        return $data;
    }
    
    public static function getT11ReceiverCodes(): array 
    {
        return [
            'T11_A_01', 'T11_A_02',
            'T11_B_01', 'T11_B_02', 'T11_B_03', 'T11_B_09',
            'T11_C_01', 'T11_C_02',
            'T11_D_01', 'T11_D_02', 'T11_D_04',
            'T11_E_01', 'T11_E_02', 'T11_E_09'
        ];
    }

    public static function getT50ReceiverCodes(): array 
    {
        return [
            'T50_A_01', 'T50_A_02',
            'T50_B_01', 'T50_B_02', 'T50_B_03', 'T50_B_04', 'T50_B_06',
            'T50_C_01', 'T50_C_02', 'T50_C_08',
            'T50_D_01', 'T50_D_02', 'T50_D_08',
            'T50_E_01', 'T50_E_02', 'T50_E_05'
        ];
    }

    /**
     * Get general condition items for receiver confirmation (Category-aware)
     * 
     * @param string $tankCat
     * @return array
     */
    public static function getGeneralConditionItems(string $tankCat = 'T75'): array
    {
        // 1. Try to fetch DYNAMIC items from Database first
        try {
            if (class_exists(\App\Models\InspectionItem::class)) {
                $query = \App\Models\InspectionItem::where('is_active', true);
                
                // Filter items based on category logic
                $query->where(function($q) use ($tankCat) {
                    $q->whereJsonContains('applicable_categories', $tankCat);
                    if ($tankCat === 'T75') {
                        $q->orWhereNull('applicable_categories');
                    }
                });

                // FOR RECEIVER: We apply strict lists and ONLY 'condition' type items
                $query->where('input_type', 'condition');

                if ($tankCat === 'T75') {
                    $query->where(function($q) {
                        $q->whereIn('category', ['b', 'external', 'general'])
                          ->orWhere('category', 'like', 'b%');
                    });
                } else {
                    // FOR T11, T50, and Others:
                    // Automatically include ALL items with input_type = 'condition' 
                    // that belong to this category.
                    // This creates a dynamic, future-proof list 
                    // that updates automatically when you add new items to DB.
                }
                
                $dynamicItems = $query->orderBy('order', 'asc')
                    ->pluck('code')
                    ->toArray();

                if (!empty($dynamicItems)) return $dynamicItems;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch dynamic inspection items for PDF: ' . $e->getMessage());
        }

        // 2. Fallback to Hardcoded List (Only if DB is empty or fails)
        return [
            'surface',
            'frame',
            'tank_plate',
            'venting_pipe',
            'explosion_proof_cover',
            'grounding_system',
            'document_container',
            'safety_label',
            'valve_box_door',
            'valve_box_door_handle',
        ];
    }
    
    /**
     * Format condition value for display
     * 
     * @param string|null $condition
     * @return string
     */
    public static function formatCondition(?string $condition): string
    {
        if (!$condition) {
            return 'N/A';
        }
        
        return match ($condition) {
            'good' => 'Good',
            'not_good' => 'Not Good',
            'need_attention' => 'Need Attention',
            'na' => 'N/A',
            'correct' => 'Correct',
            'incorrect' => 'Incorrect',
            default => ucfirst($condition),
        };
    }
    
    /**
     * Get item display name
     * 
     * @param string $itemKey
     * @return string
     */
    public static function getItemDisplayName(string $itemKey): string
    {
        $names = [
            'surface' => 'Surface',
            'frame' => 'Frame',
            'tank_plate' => 'Tank Plate',
            'venting_pipe' => 'Venting Pipe',
            'explosion_proof_cover' => 'Explosion Proof Cover',
            'grounding_system' => 'Grounding System',
            'document_container' => 'Document Container',
            'safety_label' => 'Safety Label',
            'valve_box_door' => 'Valve Box Door',
            'valve_box_door_handle' => 'Valve Box Door Handle',
            'valve_condition' => 'Valve Condition',
            'valve_position' => 'Valve Position',
            'pipe_joint' => 'Pipe Joint',
            'air_source_connection' => 'Air Source Connection',
            'esdv' => 'ESDV',
            'blind_flange' => 'Blind Flange',
            'prv' => 'PRV',
            'ibox_condition' => 'IBOX Condition',
            'pressure_gauge_condition' => 'Pressure Gauge Condition',
            'level_gauge_condition' => 'Level Gauge Condition',
            'vacuum_gauge_condition' => 'Vacuum Gauge Condition',
            'vacuum_port_suction_condition' => 'Vacuum Port Suction Condition',
            'psv1_condition' => 'PSV 1 Condition',
            'psv2_condition' => 'PSV 2 Condition',
            'psv3_condition' => 'PSV 3 Condition',
            'psv4_condition' => 'PSV 4 Condition',
        ];
        
        // DYNAMIC LOOKUP for non-standard items
        if (!isset($names[$itemKey])) {
            try {
                if (class_exists(\App\Models\InspectionItem::class)) {
                    $item = \App\Models\InspectionItem::where('code', $itemKey)->first();
                    if ($item) return $item->label;
                }
            } catch (\Exception $e) {}
        }
        
        return $names[$itemKey] ?? ucwords(str_replace(['_', 'T11', 'T50'], ' ', $itemKey));
    }
}
