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
        Schema::create('excel_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('activity_type', ['incoming_inspection', 'outgoing_inspection', 'maintenance', 'calibration']);
            $table->string('file_path'); // Stored Excel file
            $table->integer('total_rows');
            $table->integer('success_count');
            $table->integer('failed_count');
            $table->json('failed_rows')->nullable(); // Details of failed rows
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_upload_logs');
    }
};
