<?php

namespace App\PaymentGateways;

use App\Models\GatewayAccount;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayInvoicingAdapter;
use App\PaymentGateways\Definitions\AsaasPaymentGatewayDefinition;
use App\PaymentGateways\Definitions\PaymentGatewayDefinition;
use InvalidArgumentException;

class PaymentGatewayManager
{
    private array $definitions = [];

    public function __construct()
    {
        $this->register(new AsaasPaymentGatewayDefinition);
    }

    public function register(PaymentGatewayDefinition $definition): void
    {
        $this->definitions[$definition->name()] = $definition;
    }

    public function find(string $name): ?PaymentGatewayDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    public function findOrFail(string $name): PaymentGatewayDefinition
    {
        $definition = $this->find($name);

        if ($definition === null) {
            throw new InvalidArgumentException("Gateway provider '{$name}' is not supported.");
        }

        return $definition;
    }

    public function all(): array
    {
        return array_map(
            fn (PaymentGatewayDefinition $definition) => $definition->toArray(),
            array_values($this->definitions),
        );
    }

    public function providers(): array
    {
        return array_map(
            fn (PaymentGatewayDefinition $definition) => [
                'value' => $definition->name(),
                'label' => $definition->name(),
                'description' => $definition->description(),
            ],
            array_values($this->definitions),
        );
    }

    public function adapter(GatewayAccount $gatewayAccount): PaymentGatewayAdapter
    {
        $adapterClass = $this->findOrFail($gatewayAccount->name)->adapterClass();

        return app($adapterClass, ['gatewayAccount' => $gatewayAccount]);
    }

    public function invoicingAdapter(GatewayAccount $gatewayAccount): PaymentGatewayInvoicingAdapter
    {
        $definition = $this->findOrFail($gatewayAccount->name);
        $adapterClass = $definition->invoicingAdapterClass();

        if ($adapterClass === null || ! is_a($adapterClass, PaymentGatewayInvoicingAdapter::class, true)) {
            throw new InvalidArgumentException("Gateway provider '{$gatewayAccount->name}' does not support invoicing.");
        }

        return app($adapterClass, ['gatewayAccount' => $gatewayAccount]);
    }

    /** @return array<int, string> */
    public function invoicingProviderNames(): array
    {
        return array_values(array_filter(
            array_keys($this->definitions),
            fn (string $name): bool => $this->definitions[$name]->supportsInvoicing(),
        ));
    }
}
