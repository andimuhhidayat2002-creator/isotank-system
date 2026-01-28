<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\InspectionItem;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate Pressure Regulator ESDV if it exists
        // We will keep the one with the correct category ('c') and delete any others
        
        $duplicates = InspectionItem::where('code', 'pressure_regulator_esdv')->orderBy('id', 'desc')->get();
        if ($duplicates->count() > 1) {
            // Keep the first one (most recent usually, or the one we just fixed), delete others
            $keep = $duplicates->first();
            InspectionItem::where('code', 'pressure_regulator_esdv')
                ->where('id', '!=', $keep->id)
                ->delete();
        }
        
        // Also check if there is another item with specific label that is duplicate?
        // User screenshot shows "Pressure Regulator ESDV" appearing twice.
        // One under Header (Category C probably) and one under "ADDITIONAL ITEMS".
        
        // "ADDITIONAL ITEMS" usually means items that don't belong to categories A-G or are 'unmapped'.
        
        // If we fixed the category to be 'c', it should appear under 'C'.
        // If there's another row, it might have a DIFFERENT code but same label?
        
        // Let's check for any item with same label
        $dupesLabel = InspectionItem::where('label', 'Pressure Regulator ESDV')->get();
        if ($dupesLabel->count() > 1) {
             foreach($dupesLabel as $d) {
                 if ($d->code !== 'pressure_regulator_esdv') {
                     // Delete this rogue item
                     $d->delete();
                 }
             }
        }
    }

    public function down(): void
    {
    }
};
