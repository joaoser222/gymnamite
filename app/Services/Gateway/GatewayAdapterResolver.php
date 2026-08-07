<?php

namespace App\Services\Gateway;

use App\Models\GatewayAccount;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayInvoicingAdapter;
use App\PaymentGateways\PaymentGatewayManager;

class GatewayAdapterResolver
{
    public function __construct(private readonly PaymentGatewayManager $gatewayManager) {}

    public function paymentAdapter(GatewayAccount $account): PaymentGatewayAdapter
    {
        return $this->gatewayManager->adapter($account);
    }

    public function invoicingAdapter(GatewayAccount $account): PaymentGatewayInvoicingAdapter
    {
        return $this->gatewayManager->invoicingAdapter($account);
    }

    public function definition(GatewayAccount $account)
    {
        return $this->gatewayManager->find((string) $account->name);
    }

    public function isInvoicingEligible(GatewayAccount $account): bool
    {
        $definition = $this->definition($account);

        return $account->isInvoicingEligible()
            && $definition?->supportsInvoicing() === true;
    }
}
