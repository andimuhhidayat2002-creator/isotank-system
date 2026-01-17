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
        Schema::create('calibration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->string('item_name'); // Calibratable items only
            $table->text('description')->nullable();
            $table->date('planned_date')->nullable();
            $table->string('vendor')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'rejected'])->default('planned');
            $table->date('calibration_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('replacement_serial')->nullable(); // If rejected
            $table->date('replacement_calibration_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_logs');
    }
};
