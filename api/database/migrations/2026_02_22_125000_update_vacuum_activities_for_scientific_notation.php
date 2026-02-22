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
        Schema::table('vacuum_suction_activities', function (Blueprint $table) {
            // Change decimals to string to support scientific notation without data loss
            $table->string('portable_vacuum_value')->nullable()->change();
            $table->string('machine_vacuum_at_start')->nullable()->change();
            $table->string('portable_vacuum_when_machine_stops')->nullable()->change();
            $table->string('machine_vacuum_at_stop')->nullable()->change();
            $table->string('morning_vacuum_value')->nullable()->change();
            $table->string('evening_vacuum_value')->nullable()->change();

            // Add Unit Trackers
            $table->enum('portable_vacuum_unit', ['mtorr', 'scientific'])->nullable()->after('portable_vacuum_value');
            $table->enum('portable_vacuum_stop_unit', ['mtorr', 'scientific'])->nullable()->after('portable_vacuum_when_machine_stops');
            $table->enum('morning_vacuum_unit', ['mtorr', 'scientific'])->nullable()->after('morning_vacuum_value');
            $table->enum('evening_vacuum_unit', ['mtorr', 'scientific'])->nullable()->after('evening_vacuum_value');

            // Add Exact Timestamps for Machine Start and Stop
            $table->timestamp('machine_start_time')->nullable()->after('machine_vacuum_at_start');
            $table->timestamp('machine_stop_time')->nullable()->after('machine_vacuum_at_stop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacuum_suction_activities', function (Blueprint $table) {
            $table->decimal('portable_vacuum_value', 10, 4)->nullable()->change();
            $table->decimal('machine_vacuum_at_start', 10, 4)->nullable()->change();
            $table->decimal('portable_vacuum_when_machine_stops', 10, 4)->nullable()->change();
            $table->decimal('machine_vacuum_at_stop', 10, 4)->nullable()->change();
            $table->decimal('morning_vacuum_value', 10, 4)->nullable()->change();
            $table->decimal('evening_vacuum_value', 10, 4)->nullable()->change();

            $table->dropColumn([
                'portable_vacuum_unit',
                'portable_vacuum_stop_unit',
                'morning_vacuum_unit',
                'evening_vacuum_unit',
                'machine_start_time',
                'machine_stop_time'
            ]);
        });
    }
};
