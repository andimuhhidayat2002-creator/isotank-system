<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YardSlot extends Model
{
    use HasFactory;

    protected $table = 'yard_slots';

    protected $fillable = [
        'row_index',
        'col_index',
        'area_label',
        'is_active',
        'bg_color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'row_index' => 'integer',
        'col_index' => 'integer',
    ];

    public function isotankPosition()
    {
        return $this->hasOne(IsotankPosition::class, 'slot_id');
    }
}
