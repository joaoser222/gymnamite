<?php

namespace App\Models;

use App\Enums\Gateway\TransactionStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class GatewayPayment extends Model
{
    protected $table = 'gateway_payments';

    protected $fillable = [
        'gateway_reference_key',
        'payment_method',
        'payment_date',
        'status',
        'gross_value',
        'fee_value',
        'total',
        'gateway_account_id',
        'gateway_customer_id',
        'gateway_postback_id',
        'invoice_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'gross_value' => 'float',
        'payment_method' => PaymentMethod::class,
        'fee_value' => 'float',
        'total' => 'float',
        'status' => TransactionStatus::class,
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING,
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function gatewayCustomer()
    {
        return $this->belongsTo(GatewayCustomer::class);
    }

    public function gatewayAccount()
    {
        return $this->belongsTo(GatewayAccount::class);
    }

    public function gatewayPostback()
    {
        return $this->belongsTo(GatewayPostback::class);
    }
}
