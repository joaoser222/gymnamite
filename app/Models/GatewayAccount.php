<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayAccount extends Model
{
    protected $table = 'gateway_accounts';

    protected $fillable = [
        'name',
        'description',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
