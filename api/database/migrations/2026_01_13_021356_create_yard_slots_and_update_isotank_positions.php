<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename table
        if (Schema::hasTable('yard_cells')) {
            Schema::rename('yard_cells', 'yard_slots');
        } else {
            Schema::create('yard_slots', function (Blueprint $table) {
                $table->id();
                $table->integer('row_index');
                $table->integer('col_index');
                $table->string('area_label')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Update isotank_positions
        if (Schema::hasTable('isotank_positions')) {
            Schema::table('isotank_positions', function (Blueprint $table) {
                if (Schema::hasColumn('isotank_positions', 'yard_cell_id')) {
                    $table->renameColumn('yard_cell_id', 'slot_id');
                } else if (!Schema::hasColumn('isotank_positions', 'slot_id')) {
                    $table->unsignedBigInteger('slot_id')->nullable()->after('isotank_id');
                }
            });
        }

        // 3. Alter yard_slots to match requirements
        Schema::table('yard_slots', function (Blueprint $table) {
            // Add is_active if valid
            if (!Schema::hasColumn('yard_slots', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('yard_slots', 'area_label')) {
                $table->string('area_label')->nullable();
            }
        });

        // Data cleanup (if we renamed)
        if (Schema::hasColumn('yard_slots', 'cell_value')) {
             DB::statement("UPDATE yard_slots SET area_label = cell_value WHERE area_label IS NULL");
        }
        
        // Drop old columns if they exist
        Schema::table('yard_slots', function (Blueprint $table) {
             $columns = ['cell_value', 'bg_color', 'border_style', 'text_content', 'font_color', 'font_size', 'font_weight', 'colspan', 'rowspan', 'is_slot'];
             foreach ($columns as $col) {
                 if (Schema::hasColumn('yard_slots', $col)) {
                     $table->dropColumn($col);
                 }
             }
        });
    }

    public function down(): void
    {
        // Minimal rollback primarily for safety
        if (Schema::hasTable('yard_slots')) {
             Schema::rename('yard_slots', 'yard_cells');
        }
        if (Schema::hasTable('isotank_positions') && Schema::hasColumn('isotank_positions', 'slot_id')) {
             Schema::table('isotank_positions', function (Blueprint $table) {
                  $table->renameColumn('slot_id', 'yard_cell_id');
             });
        }
    }
};
