<?php

namespace App\Services\Gateway;

use App\Services\Gateway\Definitions\AsaasGatewayDefinition;
use App\Services\Gateway\Definitions\GatewayDefinition;
use InvalidArgumentException;

class GatewayManager
{
    private array $definitions = [];

    public function __construct()
    {
        $this->register(new AsaasGatewayDefinition);
    }

    public function register(GatewayDefinition $definition): void
    {
        $this->definitions[$definition->name()] = $definition;
    }

    public function find(string $name): ?GatewayDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    public function findOrFail(string $name): GatewayDefinition
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
            fn (GatewayDefinition $definition) => $definition->toArray(),
            array_values($this->definitions),
        );
    }

    public function providers(): array
    {
        return array_map(
            fn (GatewayDefinition $definition) => [
                'value' => $definition->name(),
                'label' => $definition->name(),
                'description' => $definition->description(),
            ],
            array_values($this->definitions),
        );
    }
}
