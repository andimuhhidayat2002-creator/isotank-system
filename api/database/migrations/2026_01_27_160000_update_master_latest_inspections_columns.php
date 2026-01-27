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
        Schema::table('master_latest_inspections', function (Blueprint $table) {
            // Missing Outgoing Info
            if (!Schema::hasColumn('master_latest_inspections', 'destination')) {
                $table->string('destination')->nullable()->after('psv4_replacement_calibration_date');
            }
            if (!Schema::hasColumn('master_latest_inspections', 'receiver_name')) {
                $table->string('receiver_name')->nullable()->after('destination');
            }
            if (!Schema::hasColumn('master_latest_inspections', 'receiver_confirmed_at')) {
                $table->timestamp('receiver_confirmed_at')->nullable()->after('receiver_name');
            }
            
            // Missing Metadata & Dynamic Data
            if (!Schema::hasColumn('master_latest_inspections', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('receiver_confirmed_at');
            }
            if (!Schema::hasColumn('master_latest_inspections', 'inspection_data')) {
                $table->json('inspection_data')->nullable()->after('pdf_path');
            }
            if (!Schema::hasColumn('master_latest_inspections', 'additional_details')) {
                $table->json('additional_details')->nullable()->after('inspection_data');
            }
            
            // Missing Photos
            $photos = ['photo_front', 'photo_back', 'photo_left', 'photo_right', 'photo_inside_valve_box', 'photo_additional', 'photo_extra'];
            $last = 'additional_details';
            foreach($photos as $p) {
                if (!Schema::hasColumn('master_latest_inspections', $p)) {
                    $table->text($p)->nullable()->after($last);
                }
                $last = $p;
            }

            // Missing Signature Info
            if (!Schema::hasColumn('master_latest_inspections', 'receiver_signature_path')) {
                $table->string('receiver_signature_path')->nullable()->after('photo_extra');
            }
            if (!Schema::hasColumn('master_latest_inspections', 'receiver_signed_at')) {
                $table->timestamp('receiver_signed_at')->nullable()->after('receiver_signature_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_latest_inspections', function (Blueprint $table) {
            $table->dropColumn([
                'destination', 'receiver_name', 'receiver_confirmed_at', 
                'pdf_path', 'inspection_data', 'additional_details',
                'photo_front', 'photo_back', 'photo_left', 'photo_right', 
                'photo_inside_valve_box', 'photo_additional', 'photo_extra',
                'receiver_signature_path', 'receiver_signed_at'
            ]);
        });
    }
};
