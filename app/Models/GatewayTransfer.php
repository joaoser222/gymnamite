<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayTransfer extends Model
{
    protected $fillable = [
        'gateway_reference_key',
        'gross_value',
        'fee_value',
        'total',
        'status',
        'gateway_account_id',
        'gateway_transfer_recipient_id',
        'gateway_postback_id',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(GatewayTransferRecipient::class, 'gateway_transfer_recipient_id');
    }
}
