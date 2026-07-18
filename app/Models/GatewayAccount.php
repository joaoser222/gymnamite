<?php

namespace App\Models;

use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class GatewayAccount extends Model
{
    use HasVisibility;

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
