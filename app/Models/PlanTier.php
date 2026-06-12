<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTier extends Model
{
    protected $table = 'plan_tiers';

    protected $fillable = [
        'quantity',
        'price',
        'plan_id',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
