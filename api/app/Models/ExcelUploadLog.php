<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcelUploadLog extends Model
{
    protected $fillable = [
        'uploaded_by',
        'activity_type',
        'file_path',
        'total_rows',
        'success_count',
        'failed_count',
        'failed_rows',
    ];

    protected $casts = [
        'failed_rows' => 'array',
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'activity_type' => 'string',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
