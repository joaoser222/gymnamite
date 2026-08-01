<?php

namespace App\PaymentGateways\Definitions;

abstract class PaymentGatewayDefinition
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function settings(): array;

    public function supportsInvoicing(): bool
    {
        return false;
    }

    public function invoicingAdapterClass(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'supportsInvoicing' => $this->supportsInvoicing(),
            'settings' => array_map(
                fn (PaymentGatewaySettingDefinition $setting) => [
                    'key' => $setting->key,
                    'label' => $setting->label,
                    'type' => $setting->type,
                    'required' => $setting->required,
                    'default' => $setting->default,
                    'options' => $setting->options,
                    'placeholder' => $setting->placeholder,
                    'helpText' => $setting->helpText,
                ],
                $this->settings(),
            ),
        ];
    }
}
