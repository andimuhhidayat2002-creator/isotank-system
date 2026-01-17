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
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 'surface', 'frame', 'tank_plate'
            $table->string('label'); // Display name: 'Surface Condition', 'Frame Structure'
            $table->string('category')->nullable(); // 'external', 'internal', 'safety', 'measurement'
            $table->enum('input_type', ['condition', 'text', 'number', 'date', 'boolean'])->default('condition');
            // condition = good/not_good/need_attention/na
            // text = free text
            // number = numeric input
            // date = date picker
            // boolean = yes/no
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['both', 'incoming', 'outgoing'])->default('both');
            // Options for dropdown (JSON format)
            $table->json('options')->nullable(); // For custom dropdowns
            $table->timestamps();
        });

        // Update inspection_logs to store dynamic data
        Schema::table('inspection_logs', function (Blueprint $table) {
            // Add JSON column to store dynamic inspection data
            $table->json('inspection_data')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->dropColumn('inspection_data');
        });
        
        Schema::dropIfExists('inspection_items');
    }
};
