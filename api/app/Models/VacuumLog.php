<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacuumLog extends Model
{
    protected $fillable = [
        'isotank_id',
        'vacuum_value_raw',
        'vacuum_unit_raw',
        'vacuum_value_mtorr',
        'temperature',
        'check_datetime',
        'source',
    ];

    protected $casts = [
        'check_datetime' => 'datetime',
        'vacuum_value_mtorr' => 'decimal:4',
        'temperature' => 'decimal:2',
        'source' => 'string',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }
}
