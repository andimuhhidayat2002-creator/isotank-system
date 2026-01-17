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
        Schema::create('master_isotanks', function (Blueprint $table) {
            $table->id();
            $table->string('iso_number')->unique();
            $table->string('product')->nullable();
            $table->string('owner')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model_type')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_isotanks');
    }
};
