<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceJob extends Model
{
    protected $fillable = [
        'isotank_id',
        'source_item',
        'description',
        'priority',
        'planned_date',
        'status',
        'before_photo',
        'photo_during',
        'after_photo',
        'work_description',
        'notes',
        'created_by',
        'assigned_to',
        'completed_by',
        'completed_at',
        'triggered_by_inspection_log_id',
        'sparepart',
        'qty',
        'part_damage',
        'damage_type',
        'location',
    ];


    protected $casts = [
        'planned_date' => 'date',
        'completed_at' => 'datetime',
        'status' => 'string',
    ];

    // Relationships
    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function triggeredByInspection(): BelongsTo
    {
        return $this->belongsTo(InspectionLog::class, 'triggered_by_inspection_log_id');
    }


    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeOnProgress($query)
    {
        return $query->where('status', 'on_progress');
    }

    public function scopeNotComplete($query)
    {
        return $query->where('status', 'not_complete');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
