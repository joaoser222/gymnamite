<?php

namespace App\Http\Requests;

use App\Models\GatewayAccount;
use App\PaymentGateways\Definitions\PaymentGatewaySettingDefinition;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Foundation\Http\FormRequest;

class GatewayAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->excludeUnvalidatedArrayKeys = false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'settings' => ['required', 'array'],
            'invoicing_enabled' => ['boolean'],
            'settings.invoicing' => ['nullable', 'array'],
            'settings.invoicing.service_description' => ['nullable', 'string', 'max:5000'],
            'settings.invoicing.observations' => ['nullable', 'string', 'max:5000'],
            'settings.invoicing.municipal_service_id' => ['nullable', 'string', 'max:100'],
            'settings.invoicing.municipal_service_code' => ['nullable', 'string', 'max:100'],
            'settings.invoicing.deductions' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $gatewayAccount = $this->route('gateway_account');

        if (! $gatewayAccount instanceof GatewayAccount) {
            return;
        }

        $settings = $this->input('settings', []);

        if (! is_array($settings)) {
            return;
        }

        $definition = app(PaymentGatewayManager::class)->find((string) $this->input('name', $gatewayAccount->name));

        if ($definition === null) {
            return;
        }

        $existingSettings = $gatewayAccount->settings ?? [];

        foreach ($definition->settings() as $setting) {
            if (! $setting instanceof PaymentGatewaySettingDefinition || $setting->type !== 'password') {
                continue;
            }

            if (blank($settings[$setting->key] ?? null) && array_key_exists($setting->key, $existingSettings)) {
                $settings[$setting->key] = $existingSettings[$setting->key];
            }
        }

        $invoicing = $settings['invoicing'] ?? [];

        if (is_array($invoicing)) {
            foreach (['api_key', 'access_token', 'certificate', 'certificate_password', 'password', 'token'] as $key) {
                $existingInvoicing = is_array($existingSettings['invoicing'] ?? null)
                    ? $existingSettings['invoicing']
                    : [];

                if (blank($invoicing[$key] ?? null) && array_key_exists($key, $existingInvoicing)) {
                    $invoicing[$key] = $existingInvoicing[$key];
                }
            }

            $settings['invoicing'] = $invoicing;
        }

        $this->merge(['settings' => $settings]);
    }
}
