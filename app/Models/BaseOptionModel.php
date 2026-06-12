<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseOptionModel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
    ];
}
