<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacuumSuctionActivity extends Model
{
    protected $fillable = [
        'isotank_id',
        'day_number',
        
        // Day 1
        'portable_vacuum_value',
        'temperature',
        'machine_vacuum_at_start',
        'portable_vacuum_when_machine_stops',
        'machine_vacuum_at_stop',
        'temperature_at_machine_stop',
        
        // Day 2-5 Morning
        'morning_vacuum_value',
        'morning_temperature',
        'morning_timestamp',
        
        // Day 2-5 Evening
        'evening_vacuum_value',
        'evening_temperature',
        'evening_timestamp',
        
        'notes',
        'recorded_by',
        'completed_at',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'morning_timestamp' => 'datetime',
        'evening_timestamp' => 'datetime',
        'completed_at' => 'datetime',
        // Auto-cast decimals to native PHP floats to drop trailing zeros (e.g. 1.0000 -> 1)
        'portable_vacuum_value' => 'float',
        'temperature' => 'float',
        'machine_vacuum_at_start' => 'float',
        'portable_vacuum_when_machine_stops' => 'float',
        'machine_vacuum_at_stop' => 'float',
        'temperature_at_machine_stop' => 'float',
        'morning_vacuum_value' => 'float',
        'morning_temperature' => 'float',
        'evening_vacuum_value' => 'float',
        'evening_temperature' => 'float',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
