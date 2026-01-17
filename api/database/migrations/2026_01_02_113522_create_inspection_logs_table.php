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
        Schema::create('inspection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_job_id')->constrained('inspection_jobs')->onDelete('cascade');
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->enum('inspection_type', ['incoming_inspection', 'outgoing_inspection']);
            $table->date('inspection_date');
            $table->foreignId('inspector_id')->constrained('users')->onDelete('cascade');
            
            // B. GENERAL CONDITION (10 items)
            $table->enum('surface', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('frame', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('tank_plate', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('venting_pipe', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('explosion_proof_cover', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('grounding_system', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('document_container', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('safety_label', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('valve_box_door', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('valve_box_door_handle', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            
            // C. VALVE & PIPE SYSTEM (7 items)
            $table->enum('valve_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('valve_position', ['correct', 'incorrect'])->nullable();
            $table->enum('pipe_joint', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('air_source_connection', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('esdv', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('blind_flange', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->enum('prv', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            
            // D. IBOX SYSTEM (5 items)
            $table->enum('ibox_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->decimal('ibox_pressure', 10, 2)->nullable();
            $table->decimal('ibox_temperature', 10, 2)->nullable();
            $table->decimal('ibox_level', 10, 2)->nullable();
            $table->integer('ibox_battery_percent')->nullable();
            
            // E. INSTRUMENT (Outgoing multi-stage)
            // Pressure Gauge
            $table->enum('pressure_gauge_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->decimal('pressure_1', 10, 2)->nullable();
            $table->timestamp('pressure_1_timestamp')->nullable();
            $table->decimal('pressure_2', 10, 2)->nullable();
            $table->timestamp('pressure_2_timestamp')->nullable();
            $table->string('pressure_gauge_serial_number')->nullable();
            $table->date('pressure_gauge_calibration_date')->nullable();
            $table->date('pressure_gauge_valid_until')->nullable();
            
            // Level Gauge
            $table->enum('level_gauge_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->decimal('level_1', 10, 2)->nullable();
            $table->timestamp('level_1_timestamp')->nullable();
            $table->decimal('level_2', 10, 2)->nullable();
            $table->timestamp('level_2_timestamp')->nullable();
            
            // IBOX Temperature (multi-stage)
            $table->decimal('ibox_temperature_1', 10, 2)->nullable();
            $table->timestamp('ibox_temperature_1_timestamp')->nullable();
            $table->decimal('ibox_temperature_2', 10, 2)->nullable();
            $table->timestamp('ibox_temperature_2_timestamp')->nullable();
            
            // F. VACUUM SYSTEM
            $table->decimal('vacuum_value', 10, 4)->nullable();
            $table->decimal('vacuum_temperature', 10, 2)->nullable();
            $table->timestamp('vacuum_check_datetime')->nullable();
            
            // G. PSV (1-4)
            // PSV 1
            $table->enum('psv1_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->string('psv1_serial_number')->nullable();
            $table->date('psv1_calibration_date')->nullable();
            $table->date('psv1_valid_until')->nullable();
            $table->enum('psv1_status', ['valid', 'expired', 'rejected'])->nullable();
            $table->string('psv1_replacement_serial')->nullable();
            $table->date('psv1_replacement_calibration_date')->nullable();
            
            // PSV 2
            $table->enum('psv2_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->string('psv2_serial_number')->nullable();
            $table->date('psv2_calibration_date')->nullable();
            $table->date('psv2_valid_until')->nullable();
            $table->enum('psv2_status', ['valid', 'expired', 'rejected'])->nullable();
            $table->string('psv2_replacement_serial')->nullable();
            $table->date('psv2_replacement_calibration_date')->nullable();
            
            // PSV 3
            $table->enum('psv3_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->string('psv3_serial_number')->nullable();
            $table->date('psv3_calibration_date')->nullable();
            $table->date('psv3_valid_until')->nullable();
            $table->enum('psv3_status', ['valid', 'expired', 'rejected'])->nullable();
            $table->string('psv3_replacement_serial')->nullable();
            $table->date('psv3_replacement_calibration_date')->nullable();
            
            // PSV 4
            $table->enum('psv4_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable();
            $table->string('psv4_serial_number')->nullable();
            $table->date('psv4_calibration_date')->nullable();
            $table->date('psv4_valid_until')->nullable();
            $table->enum('psv4_status', ['valid', 'expired', 'rejected'])->nullable();
            $table->string('psv4_replacement_serial')->nullable();
            $table->date('psv4_replacement_calibration_date')->nullable();
            
            // OUTGOING PHOTO FIELDS (mandatory fields, optional content)
            $table->text('photo_front')->nullable();
            $table->text('photo_back')->nullable();
            $table->text('photo_left')->nullable();
            $table->text('photo_right')->nullable();
            $table->text('photo_inside_valve_box')->nullable();
            $table->text('photo_additional')->nullable();
            
            // OUTGOING SPECIFIC
            $table->string('destination')->nullable();
            $table->string('receiver_name')->nullable();
            $table->timestamp('receiver_confirmed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_logs');
    }
};
