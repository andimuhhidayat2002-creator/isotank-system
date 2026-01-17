<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterIsotankCalibrationStatus extends Model
{
    protected $table = 'master_isotank_calibration_status';

    protected $fillable = [
        'isotank_id',
        'item_name',
        'serial_number',
        'calibration_date',
        'valid_until',
        'status',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'valid_until' => 'date',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }
}
