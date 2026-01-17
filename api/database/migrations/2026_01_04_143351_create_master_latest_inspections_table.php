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
        Schema::create('master_latest_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->unique()->constrained('master_isotanks')->onDelete('cascade');
            $table->foreignId('inspection_log_id')->nullable()->constrained('inspection_logs')->onDelete('set null');
            $table->enum('inspection_type', ['incoming_inspection', 'outgoing_inspection'])->nullable();
            $table->date('inspection_date')->nullable();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            
            // B. GENERAL CONDITION (10 items)
            $table->string('surface')->nullable();
            $table->string('frame')->nullable();
            $table->string('tank_plate')->nullable();
            $table->string('venting_pipe')->nullable();
            $table->string('explosion_proof_cover')->nullable();
            $table->string('grounding_system')->nullable();
            $table->string('document_container')->nullable();
            $table->string('safety_label')->nullable();
            $table->string('valve_box_door')->nullable();
            $table->string('valve_box_door_handle')->nullable();
            
            // C. VALVE & PIPE SYSTEM (7 items)
            $table->string('valve_condition')->nullable();
            $table->string('valve_position')->nullable();
            $table->string('pipe_joint')->nullable();
            $table->string('air_source_connection')->nullable();
            $table->string('esdv')->nullable();
            $table->string('blind_flange')->nullable();
            $table->string('prv')->nullable();
            
            // D. IBOX SYSTEM (5 items)
            $table->string('ibox_condition')->nullable();
            $table->decimal('ibox_pressure', 10, 2)->nullable();
            $table->decimal('ibox_temperature', 10, 2)->nullable();
            $table->decimal('ibox_level', 10, 2)->nullable();
            $table->integer('ibox_battery_percent')->nullable();
            
            // E. INSTRUMENT
            $table->string('pressure_gauge_condition')->nullable();
            $table->decimal('pressure_1', 10, 2)->nullable();
            $table->timestamp('pressure_1_timestamp')->nullable();
            $table->decimal('pressure_2', 10, 2)->nullable();
            $table->timestamp('pressure_2_timestamp')->nullable();
            $table->string('pressure_gauge_serial_number')->nullable();
            $table->date('pressure_gauge_calibration_date')->nullable();
            $table->date('pressure_gauge_valid_until')->nullable();
            
            $table->string('level_gauge_condition')->nullable();
            $table->decimal('level_1', 10, 2)->nullable();
            $table->timestamp('level_1_timestamp')->nullable();
            $table->decimal('level_2', 10, 2)->nullable();
            $table->timestamp('level_2_timestamp')->nullable();
            
            $table->decimal('ibox_temperature_1', 10, 2)->nullable();
            $table->timestamp('ibox_temperature_1_timestamp')->nullable();
            $table->decimal('ibox_temperature_2', 10, 2)->nullable();
            $table->timestamp('ibox_temperature_2_timestamp')->nullable();
            
            // F. VACUUM SYSTEM
            $table->decimal('vacuum_value', 10, 4)->nullable();
            $table->string('vacuum_unit')->nullable();
            $table->decimal('vacuum_temperature', 10, 2)->nullable();
            $table->timestamp('vacuum_check_datetime')->nullable();
            $table->string('vacuum_gauge_condition')->nullable();
            $table->string('vacuum_port_suction_condition')->nullable();
            
            // G. PSV (1-4)
            $table->string('psv1_condition')->nullable();
            $table->string('psv1_serial_number')->nullable();
            $table->date('psv1_calibration_date')->nullable();
            $table->date('psv1_valid_until')->nullable();
            $table->string('psv1_status')->nullable();
            $table->string('psv1_replacement_serial')->nullable();
            $table->date('psv1_replacement_calibration_date')->nullable();
            
            $table->string('psv2_condition')->nullable();
            $table->string('psv2_serial_number')->nullable();
            $table->date('psv2_calibration_date')->nullable();
            $table->date('psv2_valid_until')->nullable();
            $table->string('psv2_status')->nullable();
            $table->string('psv2_replacement_serial')->nullable();
            $table->date('psv2_replacement_calibration_date')->nullable();
            
            $table->string('psv3_condition')->nullable();
            $table->string('psv3_serial_number')->nullable();
            $table->date('psv3_calibration_date')->nullable();
            $table->date('psv3_valid_until')->nullable();
            $table->string('psv3_status')->nullable();
            $table->string('psv3_replacement_serial')->nullable();
            $table->date('psv3_replacement_calibration_date')->nullable();
            
            $table->string('psv4_condition')->nullable();
            $table->string('psv4_serial_number')->nullable();
            $table->date('psv4_calibration_date')->nullable();
            $table->date('psv4_valid_until')->nullable();
            $table->string('psv4_status')->nullable();
            $table->string('psv4_replacement_serial')->nullable();
            $table->date('psv4_replacement_calibration_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_latest_inspections');
    }
};
