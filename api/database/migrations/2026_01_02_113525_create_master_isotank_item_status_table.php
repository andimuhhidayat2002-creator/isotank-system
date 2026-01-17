<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_isotank_item_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->string('item_name'); // e.g., 'surface', 'valve_condition', 'psv1_condition'
            $table->enum('condition', ['good', 'not_good', 'need_attention', 'na', 'correct', 'incorrect'])->nullable();
            $table->timestamp('last_inspection_date')->nullable();
            $table->foreignId('last_inspection_log_id')->nullable()->constrained('inspection_logs')->onDelete('set null');
            $table->timestamps();
            
            // Ensure one record per isotank per item
            $table->unique(['isotank_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_isotank_item_status');
    }
};
