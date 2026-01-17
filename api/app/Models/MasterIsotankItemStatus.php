<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterIsotankItemStatus extends Model
{
    protected $table = 'master_isotank_item_status';

    protected $fillable = [
        'isotank_id',
        'item_name',
        'condition',
        'last_inspection_date',
        'last_inspection_log_id',
    ];

    protected $casts = [
        'last_inspection_date' => 'datetime',
        'condition' => 'string',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function lastInspectionLog(): BelongsTo
    {
        return $this->belongsTo(InspectionLog::class, 'last_inspection_log_id');
    }
}
