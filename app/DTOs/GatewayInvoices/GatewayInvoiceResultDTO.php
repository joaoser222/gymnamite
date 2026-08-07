<?php

namespace App\DTOs\GatewayInvoices;

use App\Models\GatewayInvoice;
use Spatie\LaravelData\Data;

class GatewayInvoiceResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $external_id,
        public string $status,
        public string $xml,
        public string $created_at,
        public int $gateway_account_id,
        public string $gateway_account_name,
        public ?int $gateway_payment_id = null,
    ) {}

    public static function fromModel(GatewayInvoice $invoice): static
    {
        return new static(
            id: $invoice->id,
            external_id: $invoice->external_id,
            status: $invoice->status->value,
            xml: $invoice->xml,
            created_at: $invoice->created_at?->toISOString() ?? '',
            gateway_account_id: $invoice->gateway_account_id,
            gateway_account_name: $invoice->gatewayAccount?->name ?? '',
            gateway_payment_id: $invoice->gateway_payment_id,
        );
    }
}
