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
        Schema::create('maintenance_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks')->onDelete('cascade');
            $table->string('source_item'); // Item name that triggered maintenance
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->date('planned_date')->nullable();
            $table->enum('status', ['open', 'on_progress', 'not_complete', 'closed'])->default('open');
            $table->text('before_photo')->nullable(); // From inspection
            $table->text('after_photo')->nullable(); // From maintenance activity
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_jobs');
    }
};
