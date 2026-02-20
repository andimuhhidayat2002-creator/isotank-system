<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix maintenance jobs with future completion dates (likely due to Excel import errors)
        // We set completed_at to match created_at (Opened date) for these records
        $affectedCount = DB::table('maintenance_jobs')
            ->where('completed_at', '>', '2026-02-21 00:00:00')
            ->update([
                'completed_at' => DB::raw('created_at'),
                'closed_note' => DB::raw("CONCAT(COALESCE(closed_note, ''), ' [System Fix: Adjusted future completion date to opened date]')")
            ]);

        echo "Fixed $affectedCount maintenance jobs with future dates.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to reverse this without knowing original wrong dates
    }
};
