<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterIsotankMeasurementStatus extends Model
{
    protected $table = 'master_isotank_measurement_status';

    protected $fillable = [
        'isotank_id',
        'pressure',
        'level',
        'temperature',
        'vacuum_mtorr',
        'last_measurement_at',
    ];

    protected $casts = [
        'last_measurement_at' => 'datetime',
        'pressure' => 'decimal:2',
        'level' => 'decimal:2',
        'temperature' => 'decimal:2',
        'vacuum_mtorr' => 'decimal:4',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }
}
