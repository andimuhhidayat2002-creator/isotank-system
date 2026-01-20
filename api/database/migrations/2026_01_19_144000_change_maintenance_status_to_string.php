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
        // Change the status column from enum to string to support flexible statuses (like deferred)
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            // Revert back to enum if needed, though this might fail if deferred data exists
            $table->enum('status', ['open', 'on_progress', 'not_complete', 'closed', 'deferred'])->default('open')->change();
        });
    }
};
