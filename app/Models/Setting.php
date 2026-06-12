<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasVisibility;

    protected $table = 'settings';

    protected $fillable = [
        'name',
        'content',
    ];
}
