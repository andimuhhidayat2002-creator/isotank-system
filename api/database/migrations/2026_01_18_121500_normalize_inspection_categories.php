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
            // Normalize categories to standard single letter codes
            InspectionItem::where('category', 'external')->update(['category' => 'b']);
            InspectionItem::where('category', 'general')->update(['category' => 'b']); // in case
            
            InspectionItem::where('category', 'valve')->update(['category' => 'c']);
            
            InspectionItem::where('category', 'measurement')->update(['category' => 'd']); // Mapping D/E generic to D for now
            
            InspectionItem::where('category', 'vacuum')->update(['category' => 'f']);
            
            InspectionItem::where('category', 'safety')->update(['category' => 'g']);
            
            InspectionItem::where('category', 'internal')->update(['category' => 'other']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
