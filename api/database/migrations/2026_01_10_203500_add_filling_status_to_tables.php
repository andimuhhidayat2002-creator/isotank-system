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
            $table->string('filling_status_code')->nullable()->after('status');
            $table->string('filling_status_desc')->nullable()->after('filling_status_code');
        });

        Schema::table('inspection_jobs', function (Blueprint $table) {
            $table->string('filling_status_code')->nullable()->after('receiver_name');
            $table->string('filling_status_desc')->nullable()->after('filling_status_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_isotanks', function (Blueprint $table) {
            $table->dropColumn(['filling_status_code', 'filling_status_desc']);
        });

        Schema::table('inspection_jobs', function (Blueprint $table) {
            $table->dropColumn(['filling_status_code', 'filling_status_desc']);
        });
    }
};
