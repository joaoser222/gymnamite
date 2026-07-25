<?php

namespace App\PaymentGateways;

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
}
