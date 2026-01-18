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
            // Move safety-related physical items to General Condition (B)
            // so they appear in the Dynamic General Section of the report
            $generalItems = [
                'safety_label', 
                'grounding_system', 
                'document_container', 
                'explosion_proof_cover',
                'venting_pipe'
            ];

            InspectionItem::whereIn('code', $generalItems)->update(['category' => 'b']);
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
