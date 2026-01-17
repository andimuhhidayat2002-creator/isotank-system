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
        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->string('filling_status_code')->nullable()->after('receiver_confirmed_at');
            $table->string('filling_status_desc')->nullable()->after('filling_status_code');
        });

        Schema::table('master_latest_inspections', function (Blueprint $table) {
            $table->string('filling_status_code')->nullable()->after('receiver_confirmed_at');
            $table->string('filling_status_desc')->nullable()->after('filling_status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->dropColumn(['filling_status_code', 'filling_status_desc']);
        });

        Schema::table('master_latest_inspections', function (Blueprint $table) {
            $table->dropColumn(['filling_status_code', 'filling_status_desc']);
        });
    }
};
