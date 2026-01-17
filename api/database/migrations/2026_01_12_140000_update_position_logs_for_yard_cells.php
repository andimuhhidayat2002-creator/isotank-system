<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('isotank_position_logs', function (Blueprint $table) {
            // Make old columns nullable
            $table->string('to_area')->nullable()->change();
            $table->string('to_block')->nullable()->change();
            $table->integer('to_row')->nullable()->change();
            $table->integer('to_tier')->nullable()->change();

            // Add new reference columns
            $table->foreignId('from_yard_cell_id')->nullable()->constrained('yard_cells')->onDelete('set null');
            $table->foreignId('to_yard_cell_id')->nullable()->constrained('yard_cells')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('isotank_position_logs', function (Blueprint $table) {
            $table->dropForeign(['from_yard_cell_id']);
            $table->dropForeign(['to_yard_cell_id']);
            $table->dropColumn(['from_yard_cell_id', 'to_yard_cell_id']);
            
            // Cannot revert nullable change easily in SQLite/MySQL without risk, usually acceptable to leave nullable.
            // But if strict reversion needed:
            // $table->string('to_area')->nullable(false)->change(); // This might fail if nulls exist
        });
    }
};
