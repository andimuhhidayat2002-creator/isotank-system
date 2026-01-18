<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\InspectionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (class_exists(InspectionItem::class)) {
            InspectionItem::where('code', 'psv_condition')->update(['is_active' => false]);
            // Also deactivate other potential legacy items if found
            InspectionItem::where('code', 'vacuum_condition')->update(['is_active' => false]); 
            InspectionItem::whereIn('code', ['ibox', 'ibox_system'])->update(['is_active' => false]);
            InspectionItem::whereIn('code', ['instrument', 'instrument_system'])->update(['is_active' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed usually for data cleanup, but technically we could reactivate
    }
};
