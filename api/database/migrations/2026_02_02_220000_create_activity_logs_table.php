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
        Schema::create('activity_logs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->string('action'); // e.g., 'View Media', 'Update Isotank', 'Manual Import'
            $blueprint->string('model_type')->nullable();
            $blueprint->unsignedBigInteger('model_id')->nullable();
            $blueprint->text('description')->nullable();
            $blueprint->json('details')->nullable();
            $blueprint->string('ip_address', 45)->nullable();
            $blueprint->text('user_agent')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['model_type', 'model_id']);
            $blueprint->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
