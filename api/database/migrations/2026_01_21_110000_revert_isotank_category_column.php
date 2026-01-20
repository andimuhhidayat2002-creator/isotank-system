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
        // Revert: Remove tank_category column from master_isotanks
        // User prefers to use 'product' column to differentiate types
        Schema::table('master_isotanks', function (Blueprint $table) {
            $table->dropColumn('tank_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_isotanks', function (Blueprint $table) {
            $table->string('tank_category')->default('T75')->after('iso_number');
        });
    }
};
