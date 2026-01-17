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
        Schema::create('inspection_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->enum('activity_type', ['incoming_inspection', 'outgoing_inspection']);
            $table->date('planned_date')->nullable();
            $table->string('destination')->nullable(); // For outgoing only
            $table->enum('status', ['open', 'done'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_jobs');
    }
};
