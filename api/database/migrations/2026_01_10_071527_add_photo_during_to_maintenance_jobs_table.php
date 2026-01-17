<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add photo_during to maintenance_jobs
     * 
     * Photo Rules:
     * - before_photo: from inspection (immutable)
     * - photo_during: added during maintenance work
     * - after_photo: added when maintenance completes
     */
    public function up(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->text('photo_during')->nullable()->after('before_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->dropColumn('photo_during');
        });
    }
};
