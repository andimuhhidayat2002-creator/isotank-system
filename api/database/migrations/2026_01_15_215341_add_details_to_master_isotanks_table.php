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
        Schema::table('master_isotanks', function (Blueprint $table) {
            // Identity
            // model_type already exists in create migration
            // $table->string('model_type')->nullable()->after('manufacturer');
            $table->string('manufacturer_serial_number')->nullable()->after('model_type');
            
            // Dates / Certificates
            $table->date('initial_pressure_test_date')->nullable()->after('status');
            $table->date('csc_initial_test_date')->nullable()->after('initial_pressure_test_date');
            $table->date('class_survey_expiry_date')->nullable()->after('csc_initial_test_date');
            $table->date('csc_survey_expiry_date')->nullable()->after('class_survey_expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_isotanks', function (Blueprint $table) {
            //
        });
    }
};
