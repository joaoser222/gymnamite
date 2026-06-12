<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanModality extends Model
{
    protected $table = 'plan_modalities';

    protected $fillable = [
        'plan_id',
        'modality_id',
    ];

    public function modality()
    {
        return $this->belongsTo(Modality::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
