<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasVisibility;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'modality_quantity',
        'description',
    ];

    public function tiers()
    {
        return $this->hasMany(PlanTier::class);
    }

    public function planCategory()
    {
        return $this->belongsTo(PlanCategory::class);
    }

    public function modalities()
    {
        return $this->hasMany(PlanModality::class);
    }
}
