<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old yard_slots table
        Schema::dropIfExists('yard_slots');
        
        // Create new yard_cells table
        Schema::create('yard_cells', function (Blueprint $table) {
            $table->id();
            $table->integer('row_index'); // Excel row (1-based)
            $table->integer('col_index'); // Excel col (1-based)
            $table->string('cell_value')->nullable(); // Raw cell content
            $table->string('bg_color')->nullable(); // Hex color
            $table->string('border_style')->nullable(); // CSS border string
            $table->string('text_content')->nullable(); // Display text
            $table->string('font_color')->nullable();
            $table->integer('font_size')->nullable();
            $table->string('font_weight')->nullable();
            $table->integer('colspan')->default(1);
            $table->integer('rowspan')->default(1);
            $table->boolean('is_slot')->default(false); // true if cell_value === "X"
            $table->timestamps();
            
            // Unique constraint on position
            $table->unique(['row_index', 'col_index']);
        });
        
        // Update isotank_positions to reference yard_cells
        Schema::table('isotank_positions', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['yard_area_code', 'block_code', 'row_no', 'tier_no']);
            
            // Add new reference
            $table->foreignId('yard_cell_id')->nullable()->constrained('yard_cells')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('isotank_positions', function (Blueprint $table) {
            $table->dropForeign(['yard_cell_id']);
            $table->dropColumn('yard_cell_id');
            
            // Restore old columns
            $table->string('yard_area_code')->nullable();
            $table->string('block_code')->nullable();
            $table->integer('row_no')->nullable();
            $table->integer('tier_no')->nullable();
        });
        
        Schema::dropIfExists('yard_cells');
        
        // Recreate yard_slots (basic structure)
        Schema::create('yard_slots', function (Blueprint $table) {
            $table->id();
            $table->string('yard_area_code');
            $table->string('block_code');
            $table->integer('row_no');
            $table->integer('tier_no');
            $table->string('slot_code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
