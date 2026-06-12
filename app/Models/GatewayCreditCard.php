<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayCreditCard extends Model
{
    protected $table = 'gateway_credit_cards';

    protected $fillable = [
        'gateway_card_token',
        'gateway_reference_key',
        'card_brand',
        'last_digits',
        'gateway_account_id',
        'gateway_customer_id',
        'gateway_postback_id',
    ];

    public function gatewayAccount()
    {
        return $this->belongsTo(GatewayAccount::class);
    }

    public function gatewayCustomer()
    {
        return $this->belongsTo(GatewayCustomer::class);
    }

    public function gatewayPostback()
    {
        return $this->belongsTo(GatewayPostback::class);
    }

    //
}
