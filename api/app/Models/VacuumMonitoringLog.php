<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacuumMonitoringLog extends Model
{
    protected $fillable = [
        'vacuum_suction_activity_id',
        'vacuum_value',
        'temperature',
        'period',
        'reading_at',
    ];

    protected $casts = [
        'vacuum_value' => 'float',
        'temperature' => 'float',
        'reading_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(VacuumSuctionActivity::class, 'vacuum_suction_activity_id');
    }
}
