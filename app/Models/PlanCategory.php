<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class PlanCategory extends Model
{
    use HasVisibility;

    protected $table = 'plan_categories';

    protected $fillable = [
        'name',
    ];
}
