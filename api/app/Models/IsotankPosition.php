<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsotankPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'isotank_id',
        'slot_id'
    ];

    public function isotank()
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }
    
    public function slot()
    {
        return $this->belongsTo(YardSlot::class, 'slot_id');
    }
}
