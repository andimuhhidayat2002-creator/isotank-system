<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationLog extends Model
{
    protected $fillable = [
        'isotank_id',
        'item_name',
        'description',
        'planned_date',
        'vendor',
        'status',
        'calibration_date',
        'valid_until',
        'serial_number',
        'replacement_serial',
        'replacement_calibration_date',
        'notes',
        'created_by',
        'performed_by',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'calibration_date' => 'date',
        'valid_until' => 'date',
        'replacement_calibration_date' => 'date',
        'status' => 'string',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
