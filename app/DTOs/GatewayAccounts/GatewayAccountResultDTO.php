<?php

namespace App\DTOs\GatewayAccounts;

use App\Models\GatewayAccount;
use App\PaymentGateways\Definitions\PaymentGatewaySettingDefinition;
use App\PaymentGateways\PaymentGatewayManager;
use Spatie\LaravelData\Data;

class GatewayAccountResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public bool $invoicing_enabled,
        public bool $invoicing_supported,
        public bool $invoicing_configured,
        public array $settings,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(GatewayAccount $account, PaymentGatewayManager $gatewayManager): static
    {
        $settings = $account->settings ?? [];
        $definition = $gatewayManager->find($account->name);

        if ($definition !== null) {
            foreach ($definition->settings() as $setting) {
                if ($setting instanceof PaymentGatewaySettingDefinition && $setting->type === 'password') {
                    unset($settings[$setting->key]);
                }
            }
        }

        if (isset($settings['invoicing']) && is_array($settings['invoicing'])) {
            foreach (['api_key', 'access_token', 'certificate', 'certificate_password', 'password', 'token'] as $key) {
                unset($settings['invoicing'][$key]);
            }
        }

        return new static(
            id: $account->id,
            name: $account->name,
            description: $account->description,
            invoicing_enabled: $account->invoicing_enabled,
            invoicing_supported: $account->invoicing_supported,
            invoicing_configured: $account->invoicing_configured,
            settings: $settings,
            created_at: $account->created_at?->toISOString() ?? '',
            updated_at: $account->updated_at?->toISOString() ?? '',
        );
    }
}
