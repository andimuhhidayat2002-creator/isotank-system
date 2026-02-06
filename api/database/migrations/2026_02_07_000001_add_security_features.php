<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Security Enhancement: Add failed login tracking and account lockout
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->boolean('successful')->default(false);
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at');
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('failed_login_attempts')->default(0)->after('remember_token');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->boolean('force_password_change')->default(false)->after('locked_until');
            $table->timestamp('password_changed_at')->nullable()->after('force_password_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'force_password_change',
                'password_changed_at'
            ]);
        });
    }
};
