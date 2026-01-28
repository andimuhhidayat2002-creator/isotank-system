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
            $table->string('part_damage')->nullable()->after('description');
            $table->string('damage_type')->nullable()->after('part_damage');
            $table->string('location')->nullable()->after('damage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->dropColumn(['part_damage', 'damage_type', 'location']);
        });
    }
};
