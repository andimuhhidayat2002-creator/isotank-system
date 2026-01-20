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
        // Use raw SQL to modify the enum column. Check driver to avoid SQLite errors.
        if (DB::getDriverName() === 'mysql') {
             DB::statement("ALTER TABLE maintenance_jobs MODIFY COLUMN status ENUM('open', 'on_progress', 'not_complete', 'closed', 'deferred') NOT NULL DEFAULT 'open'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE maintenance_jobs MODIFY COLUMN status ENUM('open', 'on_progress', 'not_complete', 'closed') NOT NULL DEFAULT 'open'");
        }
    }
};
