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
        // 1. Update vacuum_logs to match GOLD PROMPT
        Schema::table('vacuum_logs', function (Blueprint $table) {
            $table->string('vacuum_value_raw')->after('isotank_id')->nullable();
            $table->enum('vacuum_unit_raw', ['torr', 'mtorr', 'scientific'])->after('vacuum_value_raw')->nullable();
            $table->renameColumn('vacuum_value', 'vacuum_value_mtorr');
        });

        // 2. Create master_isotank_measurement_status
        Schema::create('master_isotank_measurement_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->decimal('pressure', 10, 2)->nullable();
            $table->decimal('level', 10, 2)->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('vacuum_mtorr', 10, 4)->nullable();
            $table->timestamp('last_measurement_at')->nullable();
            $table->timestamps();
            
            $table->unique('isotank_id');
        });

        // 3. Create master_isotank_calibration_status
        Schema::create('master_isotank_calibration_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->string('item_name'); // e.g., 'pressure_gauge', 'psv1', 'psv2', etc.
            $table->string('serial_number')->nullable();
            $table->date('calibration_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->enum('status', ['valid', 'expired', 'rejected'])->default('valid');
            $table->timestamps();
            
            $table->unique(['isotank_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_isotank_calibration_status');
        Schema::dropIfExists('master_isotank_measurement_status');
        Schema::table('vacuum_logs', function (Blueprint $table) {
            $table->renameColumn('vacuum_value_mtorr', 'vacuum_value');
            $table->dropColumn(['vacuum_value_raw', 'vacuum_unit_raw']);
        });
    }
};
