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
        Schema::table('inspection_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('inspection_logs', 'receiver_signature_path')) {
                $table->string('receiver_signature_path')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('inspection_logs', 'receiver_signed_at')) {
                $table->timestamp('receiver_signed_at')->nullable()->after('receiver_signature_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->dropColumn(['receiver_signature_path', 'receiver_signed_at']);
        });
    }
};
