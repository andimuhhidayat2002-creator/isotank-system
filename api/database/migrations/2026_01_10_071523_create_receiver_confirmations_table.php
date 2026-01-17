<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * RECEIVER CONFIRMATION - OUTGOING ONLY (IMMUTABLE)
     * 
     * Rules:
     * - INSERT ONLY (never update/delete)
     * - One record per item per inspection
     * - Receiver confirms ONLY general condition items (B)
     */
    public function up(): void
    {
        Schema::create('receiver_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_log_id')->constrained('inspection_logs')->onDelete('cascade');
            $table->string('item_name'); // Item from general condition (B)
            $table->string('inspector_condition'); // READ ONLY - from inspection log
            $table->enum('receiver_decision', ['ACCEPT', 'REJECT']); // Receiver's decision
            $table->text('receiver_remark')->nullable(); // Optional remark
            $table->string('receiver_photo_path')->nullable(); // Optional photo
            $table->timestamps();
            
            // Ensure one confirmation per item per inspection
            $table->unique(['inspection_log_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiver_confirmations');
    }
};
