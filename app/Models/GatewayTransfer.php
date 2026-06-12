<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayTransfer extends Model
{
    protected $fillable = [
        'gateway_reference_key',
        'gross_value',
        'fee_value',
        'total',
        'status',
        'gateway_account_id',
        'gateway_postback_id',
    ];

    //
}
