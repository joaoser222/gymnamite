<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $table = 'transfers';

    protected $fillable = [
        'annotations',
        'value',
        'status',
        'account_id',
    ];
}
