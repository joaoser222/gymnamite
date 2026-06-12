<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayPostback extends Model
{
    protected $fillable = [
        'postback_event',
        'postback_type',
        'payload',
        'status',
        'gateway_account_id',
    ];

    //
}
