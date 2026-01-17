<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterIsotankComponent extends Model
{
    protected $fillable = [
        'isotank_id',
        'component_type', // PG, PSV, PRV
        'position_code',
        'serial_number',
        'manufacturer',
        'certificate_number',
        'set_pressure',
        'pressure_unit',
        'last_calibration_date',
        'expiry_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'set_pressure' => 'decimal:2',
        'last_calibration_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationship
    public function isotank()
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }

    // Scopes for easy filtering
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeType($query, $type)
    {
        return $query->where('component_type', $type);
    }
    
    // Scopes for specific parts
    public function scopePressureGauges($query)
    {
        return $query->where('component_type', 'PG');
    }

    public function scopeSafetyValves($query)
    {
        return $query->where('component_type', 'PSV');
    }

    public function scopeReliefValves($query)
    {
        return $query->where('component_type', 'PRV');
    }
}
