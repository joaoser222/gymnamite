<?php

namespace App\Models;

use App\Enums\Gateway\TransactionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

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
        'payment_date' => 'date:Y-m-d',
        'gross_value' => 'float',
        'payment_method' => PaymentMethod::class,
        'fee_value' => 'float',
        'total' => 'float',
        'status' => TransactionStatus::class,
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING,
    ];

    protected static function booted(): void
    {
        static::created(function (GatewayPayment $payment): void {
            $payment->syncInvoiceStatus();
        });

        static::updated(function (GatewayPayment $payment): void {
            $payment->syncInvoiceStatus();
        });
    }

    private function syncInvoiceStatus(): void
    {
        $this->loadMissing('invoice');

        if ($this->invoice === null || ! $this->invoice->usesGatewayPaymentMethod()) {
            return;
        }

        match ($this->status) {
            TransactionStatus::PAID => $this->invoice->update([
                'payment_date' => $this->payment_date ?? Date::today(),
                'paid_value' => $this->invoice->total,
                'status' => InvoiceStatus::PAID,
            ]),
            TransactionStatus::OVERDUE => $this->invoice->update([
                'status' => InvoiceStatus::OVERDUED,
            ]),
            TransactionStatus::CANCELED => $this->invoice->update([
                'status' => InvoiceStatus::CANCELED,
            ]),
            default => $this->invoice->status === InvoiceStatus::PENDING
                ? $this->invoice->update(['status' => InvoiceStatus::WAITING])
                : null,
        };
    }

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
