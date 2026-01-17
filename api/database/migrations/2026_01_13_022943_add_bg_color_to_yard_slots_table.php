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
        Schema::table('yard_slots', function (Blueprint $table) {
            $table->string('bg_color')->nullable()->after('area_label');
        });
    }

    public function down(): void
    {
        Schema::table('yard_slots', function (Blueprint $table) {
            $table->dropColumn('bg_color');
        });
    }
};
