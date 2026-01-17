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
        Schema::create('master_isotank_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            
            // Type: 'PG' (Gauge), 'PSV' (Safety Valve), 'PRV' (Relief Valve - External)
            $table->string('component_type')->index(); 
            
            // Position/Code: 'Main', '1', '2', 'Top', etc. important for multiple PSVs
            $table->string('position_code')->nullable();
            
            // Physical Identity
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            
            // Certificate Data
            $table->string('certificate_number')->nullable();
            $table->decimal('set_pressure', 8, 2)->nullable(); // For Valves (MPa/Bar)
            $table->string('pressure_unit')->default('MPa');
            
            // Calendar
            $table->date('last_calibration_date')->nullable();
            $table->date('expiry_date')->nullable(); // The source of truth for alerts
            
            // Logic Control
            $table->boolean('is_active')->default(true); // If a part is removed/replaced
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_isotank_components');
    }
};
