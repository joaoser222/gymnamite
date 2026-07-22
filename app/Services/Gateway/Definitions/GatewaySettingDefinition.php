<?php

namespace App\Services\Gateway\Definitions;

class GatewaySettingDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required = true,
        public readonly mixed $default = null,
        public readonly ?array $options = null,
        public readonly ?string $placeholder = null,
        public readonly ?string $helpText = null,
    ) {}
}
