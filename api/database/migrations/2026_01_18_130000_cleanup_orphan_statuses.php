<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MasterIsotankItemStatus;
use App\Models\InspectionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(MasterIsotankItemStatus::class) && class_exists(InspectionItem::class)) {
             // Get All Valid Item Codes from Database (Active & Inactive to be safe, usually we only care about active)
             // Let's keep ALL configured items
             $validCodes = InspectionItem::pluck('code')->toArray();
             
             // Hardcoded Legacy Codes that MUST be kept for specialized sections
             $hardcoded = [
                 // IBOX Section
                 'ibox_condition',
                 
                 // Instruments Section
                 'pressure_gauge_condition',
                 'level_gauge_condition',
                 
                 // Vacuum Section
                 'vacuum_gauge_condition', 
                 'vacuum_port_suction_condition',
                 
                 // PSV Section
                 'psv1_condition', 'psv2_condition', 'psv3_condition', 'psv4_condition'
             ];
             
             $keep = array_merge($validCodes, $hardcoded);
             
             // Delete orphan statuses (garbage data like 'DG_1972...' labels used as keys)
             // This cleans up the "Others" section in Isotank Details
             MasterIsotankItemStatus::whereNotIn('item_name', $keep)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data cleanup cannot be reversed easily
    }
};
