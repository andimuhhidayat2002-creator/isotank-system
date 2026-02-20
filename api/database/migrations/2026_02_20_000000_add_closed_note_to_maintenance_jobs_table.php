<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->text('closed_note')->nullable()->after('work_description')
                ->comment('Note added when maintenance is closed, e.g. via inspection form quick repair');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_jobs', function (Blueprint $table) {
            $table->dropColumn('closed_note');
        });
    }
};
