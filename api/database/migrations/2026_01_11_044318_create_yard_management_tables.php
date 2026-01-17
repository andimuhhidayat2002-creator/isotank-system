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
        Schema::create('yard_slots', function (Blueprint $table) {
            $table->id();
            $table->string('yard_area_code');
            $table->string('block_code');
            $table->integer('row_no');
            $table->integer('tier_no');
            $table->string('slot_code')->unique();
            $table->string('slot_type')->nullable(); // normal / buffer / restricted
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('isotank_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->unique()->constrained('master_isotanks')->onDelete('cascade');
            $table->string('yard_area_code');
            $table->string('block_code');
            $table->integer('row_no');
            $table->integer('tier_no');
            $table->timestamps();
        });

        Schema::create('isotank_position_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isotank_id')->constrained('master_isotanks');
            $table->string('from_area')->nullable();
            $table->string('from_block')->nullable();
            $table->integer('from_row')->nullable();
            $table->integer('from_tier')->nullable();
            $table->string('to_area');
            $table->string('to_block');
            $table->integer('to_row');
            $table->integer('to_tier');
            $table->foreignId('moved_by')->nullable()->constrained('users');
            $table->timestamp('moved_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isotank_position_logs');
        Schema::dropIfExists('isotank_positions');
        Schema::dropIfExists('yard_slots');
    }
};
