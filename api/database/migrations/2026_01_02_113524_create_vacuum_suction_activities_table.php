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
        Schema::create('vacuum_suction_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->integer('day_number'); // 1-5
            
            // DAY 1 FULL
            $table->decimal('portable_vacuum_value', 10, 4)->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->decimal('machine_vacuum_at_start', 10, 4)->nullable();
            $table->decimal('portable_vacuum_when_machine_stops', 10, 4)->nullable();
            $table->decimal('machine_vacuum_at_stop', 10, 4)->nullable();
            $table->decimal('temperature_at_machine_stop', 10, 2)->nullable();
            
            // DAY 2-5 MORNING
            $table->decimal('morning_vacuum_value', 10, 4)->nullable();
            $table->decimal('morning_temperature', 10, 2)->nullable();
            $table->timestamp('morning_timestamp')->nullable();
            
            // DAY 2-5 EVENING
            $table->decimal('evening_vacuum_value', 10, 4)->nullable();
            $table->decimal('evening_temperature', 10, 2)->nullable();
            $table->timestamp('evening_timestamp')->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacuum_suction_activities');
    }
};
