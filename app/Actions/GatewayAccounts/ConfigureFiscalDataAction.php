<?php

namespace App\Actions\GatewayAccounts;

use App\Actions\BaseAction;
use App\DTOs\GatewayAccounts\ActionResultDTO;
use App\DTOs\GatewayAccounts\ConfigureFiscalDataDTO;
use App\Models\GatewayAccount;
use App\PaymentGateways\PaymentGatewayManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfigureFiscalDataAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = GatewayAccount::class;

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof ConfigureFiscalDataDTO) {
            throw new \InvalidArgumentException('ConfigureFiscalDataAction requires a ConfigureFiscalDataDTO.');
        }

        $dto = $input;
        $account = GatewayAccount::query()->findOrFail($dto->id);

        $definition = $this->gatewayManager->find((string) $account->name);

        if ($definition?->supportsInvoicing() !== true) {
            throw new ValidationException(
                Validator::make([], [
                    'gateway_account' => ['O provedor desta conta não suporta emissão fiscal.'],
                ]),
            );
        }

        $payload = array_filter([
            'municipalServiceCode' => $dto->municipal_service_code,
            'municipalServiceName' => $dto->municipal_service_name,
            'serviceDescription' => $dto->service_description,
            'observations' => $dto->observations,
            'incentivizedTax' => $dto->incentivized_tax,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->gatewayManager
            ->invoicingAdapter($account)
            ->configureFiscalData($payload);

        $settings = $account->settings ?? [];
        $settings['invoicing'] = array_merge($settings['invoicing'] ?? [], [
            'municipal_service_code' => $dto->municipal_service_code,
            'service_description' => $dto->service_description,
            'municipal_service_name' => $dto->municipal_service_name,
            'observations' => $dto->observations,
            'incentivized_tax' => $dto->incentivized_tax,
            'fiscal_configuration_at' => now()->toISOString(),
        ]);

        $account->update([
            'settings' => $settings,
        ]);

        return ActionResultDTO::success(
            [
                'configuration' => array_intersect_key($response, array_flip([
                    'municipalServiceCode',
                    'municipalServiceName',
                    'serviceDescription',
                    'observations',
                    'incentivizedTax',
                    'status',
                ])),
                'invoicing_configured' => $account->refresh()->invoicing_configured,
                'invoicing_supported' => $account->invoicing_supported,
            ],
            'Configuração fiscal atualizada com sucesso.',
        );
    }
}
