<?php

namespace App\PaymentGateways\Contracts;

use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;

interface PaymentGatewayInvoicingAdapter
{
    public function requestInvoice(GatewayPayment $payment, array $configuration, ?GatewayInvoice $invoice = null): GatewayInvoice;

    public function getMunicipalOptions(): array;

    public function configureFiscalData(array $data): array;

    public function getMunicipalServices(array $filters = []): array;

    public function scheduleInvoice(GatewayInvoice $invoice): GatewayInvoice;

    public function findInvoice(GatewayInvoice $invoice): ?array;

    public function authorizeInvoice(GatewayInvoice $invoice): GatewayInvoice;

    public function cancelInvoice(GatewayInvoice $invoice, ?string $reason = null): GatewayInvoice;
}
