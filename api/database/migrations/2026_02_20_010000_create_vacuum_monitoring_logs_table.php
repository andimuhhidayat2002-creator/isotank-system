<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacuum_monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacuum_suction_activity_id')->constrained('vacuum_suction_activities')->onDelete('cascade');
            $table->decimal('vacuum_value', 15, 4);
            $table->decimal('temperature', 8, 2);
            $table->string('period')->comment('Morning, Evening, Extra Day, etc');
            $table->timestamp('reading_at')->useCurrent();
            $table->timestamps();
        });

        // Also add status to activities table if missing, to track lifecycle
        Schema::table('vacuum_suction_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('vacuum_suction_activities', 'status')) {
                $table->string('status')->default('ongoing')->after('day_number');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacuum_monitoring_logs');
    }
};
