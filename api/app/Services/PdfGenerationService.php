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
        // Load relationships
        $inspectionLog->load(['isotank', 'inspector', 'inspectionJob']);
        
        // Get open maintenance items for this isotank
        $openMaintenance = MaintenanceJob::where('isotank_id', $inspectionLog->isotank_id)
            ->where('status', 'open')
            ->with('triggeredByInspection')
            ->get();
        
        // Prepare data
        $data = [
            'type' => 'incoming',
            'inspection' => $inspectionLog,
            'isotank' => $inspectionLog->isotank,
            'inspector' => $inspectionLog->inspector,
            'job' => $inspectionLog->inspectionJob,
            'openMaintenance' => $openMaintenance,
            'generatedAt' => now(),
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.inspection_report', $data);
        
        // Store PDF
        $filename = 'inspection_' . $inspectionLog->id . '_' . time() . '.pdf';
        $path = 'inspection_pdfs/' . $filename;
        
        Storage::disk('public')->put($path, $pdf->output());
        
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
            'generatedAt' => now(),
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('pdf.inspection_report', $data);
        
        // Store PDF
        $filename = 'inspection_outgoing_' . $inspectionLog->id . '_' . time() . '.pdf';
        $path = 'inspection_pdfs/' . $filename;
        
        Storage::disk('public')->put($path, $pdf->output());
        
        // Update inspection log with PDF path
        $inspectionLog->update(['pdf_path' => $path]);
        
        return $path;
    }
    
    /**
     * Get general condition items for receiver confirmation (Category-aware)
     * 
     * @param string $tankCat
     * @return array
     */
    public static function getGeneralConditionItems(string $tankCat = 'T75'): array
    {
        // 1. Try to fetch DYNAMIC items from Database first (Single Source of Truth)
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

                // For Receiver Confirmation, T75 has specific sections. 
                // T11/T50 shows everything tagged.
                if ($tankCat === 'T75') {
                    $query->where(function($q) {
                        $q->whereIn('category', ['b', 'external', 'general'])
                          ->orWhere('category', 'like', 'b%');
                    });
                }
                
                $dynamicItems = $query->orderBy('order', 'asc')
                    ->pluck('code')
                    ->toArray();

                // If we found items in DB, utilize them strictly to match Inspector's view
                if (!empty($dynamicItems)) {
                    return $dynamicItems;
                }
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
