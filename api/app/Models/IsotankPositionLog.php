<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line

class IsotankPositionLog extends Model
{
    use HasFactory; // Added this line

    public $timestamps = false; // Added this line

    protected $fillable = [ // Added this block
        'isotank_id',
        'from_area',
        'from_block',
        'from_row',
        'from_tier',
        'to_area',
        'to_block',
        'to_row',
        'to_tier',
        'from_yard_cell_id',
        'to_yard_cell_id',
        'moved_by',
        'moved_at'
    ];

    public function user() // Added this method
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
