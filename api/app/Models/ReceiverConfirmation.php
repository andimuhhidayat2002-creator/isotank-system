<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RECEIVER CONFIRMATION MODEL (IMMUTABLE)
 * 
 * Rules:
 * - INSERT ONLY (never update/delete)
 * - One record per item per inspection
 * - Tracks ACCEPT/REJECT decisions for general condition items
 */
class ReceiverConfirmation extends Model
{
    protected $fillable = [
        'inspection_log_id',
        'item_name',
        'inspector_condition',
        'receiver_decision',
        'receiver_remark',
        'receiver_photo_path',
    ];

    protected $casts = [
        'receiver_decision' => 'string',
    ];

    // Relationships
    public function inspectionLog(): BelongsTo
    {
        return $this->belongsTo(InspectionLog::class, 'inspection_log_id');
    }

    // Scopes
    public function scopeAccepted($query)
    {
        return $query->where('receiver_decision', 'ACCEPT');
    }

    public function scopeRejected($query)
    {
        return $query->where('receiver_decision', 'REJECT');
    }
}
