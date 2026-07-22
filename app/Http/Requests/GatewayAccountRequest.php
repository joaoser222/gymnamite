<?php

namespace App\Http\Requests;

use App\Models\GatewayAccount;
use App\Services\Gateway\Definitions\GatewaySettingDefinition;
use App\Services\Gateway\GatewayManager;
use Illuminate\Foundation\Http\FormRequest;

class GatewayAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'settings' => ['required', 'array'],
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

        $definition = app(GatewayManager::class)->find((string) $this->input('name', $gatewayAccount->name));

        if ($definition === null) {
            return;
        }

        $existingSettings = $gatewayAccount->settings ?? [];

        foreach ($definition->settings() as $setting) {
            if (! $setting instanceof GatewaySettingDefinition || $setting->type !== 'password') {
                continue;
            }

            if (blank($settings[$setting->key] ?? null) && array_key_exists($setting->key, $existingSettings)) {
                $settings[$setting->key] = $existingSettings[$setting->key];
            }
        }

        $this->merge(['settings' => $settings]);
    }
}
