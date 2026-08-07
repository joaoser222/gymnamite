<?php

namespace App\DTOs\GatewayPayments;

use App\Models\GatewayPayment;
use Spatie\LaravelData\Data;

class GatewayPaymentResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $external_id,
        public string $status,
        public string $payment_method,
        public float $amount,
        public string $created_at,
        public int $gateway_account_id,
        public string $gateway_account_name,
        public ?int $invoice_id = null,
    ) {}

    public static function fromModel(GatewayPayment $payment): static
    {
        return new static(
            id: $payment->id,
            external_id: $payment->external_id,
            status: $payment->status->value,
            payment_method: $payment->payment_method->value,
            amount: $payment->amount,
            created_at: $payment->created_at?->toISOString() ?? '',
            gateway_account_id: $payment->gateway_account_id,
            gateway_account_name: $payment->gatewayAccount?->name ?? '',
            invoice_id: $payment->invoice_id,
        );
    }
}
