<?php

namespace App\Services\Gateway\Definitions;

abstract class GatewayDefinition
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function settings(): array;

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'settings' => array_map(
                fn (GatewaySettingDefinition $setting) => [
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
