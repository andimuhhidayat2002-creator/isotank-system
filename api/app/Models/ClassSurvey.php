<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSurvey extends Model
{
    protected $fillable = [
        'isotank_id',
        'survey_date',
        'next_survey_date',
    ];

    protected $casts = [
        'survey_date' => 'date',
        'next_survey_date' => 'date',
    ];

    public function isotank(): BelongsTo
    {
        return $this->belongsTo(MasterIsotank::class, 'isotank_id');
    }
}
