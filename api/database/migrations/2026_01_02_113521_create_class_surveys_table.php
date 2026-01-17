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
        Schema::create('class_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->date('survey_date');
            $table->date('next_survey_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_surveys');
    }
};
