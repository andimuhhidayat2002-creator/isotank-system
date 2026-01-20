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
        if (Schema::hasTable('master_isotanks') && !Schema::hasColumn('master_isotanks', 'tank_category')) {
            Schema::table('master_isotanks', function (Blueprint $table) {
                // Determine position
                $after = 'iso_number';
                if (!Schema::hasColumn('master_isotanks', 'iso_number')) {
                    $after = 'id'; // Fallback
                }
                
                $table->string('tank_category')->default('T75')->after($after)->comment('T75, T11, T50, etc.');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as this is a fix/ensure migration
    }
};
