<?php

namespace App\Actions\GatewayAccounts;

use App\Actions\BaseAction;
use App\DTOs\GatewayAccounts\ActionResultDTO;
use App\DTOs\GatewayAccounts\CreateGatewayAccountDTO;
use App\DTOs\GatewayAccounts\GatewayAccountResultDTO;
use App\Models\GatewayAccount;
use App\PaymentGateways\PaymentGatewayManager;

class CreateGatewayAccountAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = GatewayAccount::class;

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateGatewayAccountDTO) {
            throw new \InvalidArgumentException('CreateGatewayAccountAction requires a CreateGatewayAccountDTO.');
        }

        $dto = $input;
        $account = GatewayAccount::query()->create([
            'name' => $dto->name,
            'description' => $dto->description,
            'settings' => $dto->settings,
            'invoicing_enabled' => $dto->invoicing_enabled,
        ]);

        return ActionResultDTO::success(
            GatewayAccountResultDTO::fromModel($account, $this->gatewayManager),
            'Conta de gateway criada com sucesso.',
        );
    }
}
