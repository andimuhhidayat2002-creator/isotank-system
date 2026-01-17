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
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->foreignId('triggered_by_inspection_log_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('inspection_logs')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->dropForeign(['triggered_by_inspection_log_id']);
            $table->dropColumn('triggered_by_inspection_log_id');
        });
    }
};
