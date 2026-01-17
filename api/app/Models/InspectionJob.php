<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionJob extends Model
{
    protected $fillable = [
        'isotank_id',
        'activity_type',
        'planned_date',
        'destination',
        'receiver_name',
        'filling_status_code',
        'filling_status_desc',
        'status', // open, in_progress, done
        'inspector_id',
        'started_at',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'activity_type' => 'string',
        'status' => 'string',
    ];

    // Relationships
    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    public function inspectionLogs()
    {
        return $this->hasMany(InspectionLog::class, 'inspection_job_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function scopeIncoming($query)
    {
        return $query->where('activity_type', 'incoming_inspection');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('activity_type', 'outgoing_inspection');
    }
}
