<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'category',
        'input_type',
        'description',
        'order',
        'is_required',
        'is_active',
        'applies_to',
        'options',
        'applicable_categories',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'options' => 'array',
        'order' => 'integer',
        'applicable_categories' => 'array',
    ];

    /**
     * Scope to get only active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get items for specific inspection type
     */
    public function scopeForType($query, $type)
    {
        return $query->where(function($q) use ($type) {
            $q->where('applies_to', 'both')
              ->orWhere('applies_to', $type);
        });
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('label');
    }
}
