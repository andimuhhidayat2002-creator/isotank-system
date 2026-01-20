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
        // 1. Update Users Table for Inspector Signature
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
            $table->timestamp('signature_updated_at')->nullable()->after('signature_path');
        });

        // 2. Update Inspection Logs for Receiver Signature
        // We place this on inspection_logs (header) as it represents the single handover event.
        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->string('receiver_signature_path')->nullable()->after('receiver_confirmed_at');
            $table->timestamp('receiver_signed_at')->nullable()->after('receiver_signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_updated_at']);
        });

        Schema::table('inspection_logs', function (Blueprint $table) {
            $table->dropColumn(['receiver_signature_path', 'receiver_signed_at']);
        });
    }
};
