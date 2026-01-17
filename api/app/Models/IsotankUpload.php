<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsotankUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'filepath',
        'uploaded_by',
        'total_rows',
        'success_count',
        'error_count',
        'error_details',
    ];

    protected $casts = [
        'error_details' => 'array',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
