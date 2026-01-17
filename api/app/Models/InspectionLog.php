<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionLog extends Model
{
    protected $fillable = [
        'inspection_job_id',
        'isotank_id',
        'inspection_type',
        'inspection_date',
        'inspector_id',
        'is_draft',
        
        // B. GENERAL CONDITION
        'surface',
        'frame',
        'tank_plate',
        'venting_pipe',
        'explosion_proof_cover',
        'grounding_system',
        'document_container',
        'safety_label',
        'valve_box_door',
        'valve_box_door_handle',
        
        // C. VALVE & PIPE SYSTEM
        'valve_condition',
        'valve_position',
        'pipe_joint',
        'air_source_connection',
        'esdv',
        'blind_flange',
        'prv',
        
        // D. IBOX SYSTEM
        'ibox_condition',
        'ibox_pressure',
        'ibox_temperature',
        'ibox_level',
        'ibox_battery_percent',
        
        // E. INSTRUMENT
        'pressure_gauge_condition',
        'pressure_1',
        'pressure_1_timestamp',
        'pressure_2',
        'pressure_2_timestamp',
        'pressure_gauge_serial_number',
        'pressure_gauge_calibration_date',
        'pressure_gauge_valid_until',
        
        'level_gauge_condition',
        'level_1',
        'level_1_timestamp',
        'level_2',
        'level_2_timestamp',
        
        'ibox_temperature_1',
        'ibox_temperature_1_timestamp',
        'ibox_temperature_2',
        'ibox_temperature_2_timestamp',
        
        // F. VACUUM
        'vacuum_value',
        'vacuum_unit',
        'vacuum_temperature',
        'vacuum_check_datetime',
        'vacuum_gauge_condition',
        'vacuum_port_suction_condition',
        
        // G. PSV 1-4
        'psv1_condition', 'psv1_serial_number', 'psv1_calibration_date', 'psv1_valid_until', 'psv1_status', 'psv1_replacement_serial', 'psv1_replacement_calibration_date',
        'psv2_condition', 'psv2_serial_number', 'psv2_calibration_date', 'psv2_valid_until', 'psv2_status', 'psv2_replacement_serial', 'psv2_replacement_calibration_date',
        'psv3_condition', 'psv3_serial_number', 'psv3_calibration_date', 'psv3_valid_until', 'psv3_status', 'psv3_replacement_serial', 'psv3_replacement_calibration_date',
        'psv4_condition', 'psv4_serial_number', 'psv4_calibration_date', 'psv4_valid_until', 'psv4_status', 'psv4_replacement_serial', 'psv4_replacement_calibration_date',
        
        // PHOTOS
        'photo_front',
        'photo_back',
        'photo_left',
        'photo_right',
        'photo_inside_valve_box',
        'photo_additional',
        'photo_extra',
        
        // OUTGOING
        'destination',
        'receiver_name',
        'receiver_confirmed_at',
        'additional_details',
        'pdf_path',
        'filling_status_code',
        'filling_status_desc',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'inspection_date' => 'date',
        'pressure_1_timestamp' => 'datetime',
        'pressure_2_timestamp' => 'datetime',
        'level_1_timestamp' => 'datetime',
        'level_2_timestamp' => 'datetime',
        'ibox_temperature_1_timestamp' => 'datetime',
        'ibox_temperature_2_timestamp' => 'datetime',
        'vacuum_check_datetime' => 'datetime',
        'pressure_gauge_calibration_date' => 'date',
        'pressure_gauge_valid_until' => 'date',
        'psv1_calibration_date' => 'date',
        'psv1_valid_until' => 'date',
        'psv1_replacement_calibration_date' => 'date',
        'psv2_calibration_date' => 'date',
        'psv2_valid_until' => 'date',
        'psv2_replacement_calibration_date' => 'date',
        'psv3_calibration_date' => 'date',
        'psv3_valid_until' => 'date',
        'psv3_replacement_calibration_date' => 'date',
        'psv4_calibration_date' => 'date',
        'psv4_valid_until' => 'date',
        'psv4_replacement_calibration_date' => 'date',
        'receiver_confirmed_at' => 'datetime',
        'additional_details' => 'array',
    ];

    // Relationships
    public function inspectionJob(): BelongsTo
    {
        return $this->belongsTo(InspectionJob::class, 'inspection_job_id');
    }

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
