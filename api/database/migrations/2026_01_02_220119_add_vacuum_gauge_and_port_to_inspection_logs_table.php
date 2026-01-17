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
            $table->enum('vacuum_gauge_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable()->after('vacuum_check_datetime');
            $table->enum('vacuum_port_suction_condition', ['good', 'not_good', 'need_attention', 'na'])->nullable()->after('vacuum_gauge_condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_logs', function (Blueprint $table) {
            //
        });
    }
};
