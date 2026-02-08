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
        // 1. Check if completion_date exists and migrate data if present
        if (Schema::hasColumn('maintenance_jobs', 'completion_date')) {
            DB::statement('UPDATE maintenance_jobs SET completed_at = completion_date WHERE completed_at IS NULL AND completion_date IS NOT NULL');
        }

        // 2. Backfill completed_at from updated_at for closed jobs (if still null)
        // Check varied statuses for 'closed'
        DB::table('maintenance_jobs')
            ->whereIn('status', ['closed', 'done', 'completed', 'finish', 'Closed', 'Done', 'Completed'])
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);

        // 3. Normalize status to lowercase
        DB::statement('UPDATE maintenance_jobs SET status = LOWER(status)');
        
        // 4. Normalize priority to lowercase
        DB::statement('UPDATE maintenance_jobs SET priority = LOWER(priority)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            //
        });
    }
};
