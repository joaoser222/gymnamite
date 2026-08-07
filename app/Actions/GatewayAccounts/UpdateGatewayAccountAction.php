<?php

namespace App\Actions\GatewayAccounts;

use App\Actions\BaseAction;
use App\DTOs\GatewayAccounts\ActionResultDTO;
use App\DTOs\GatewayAccounts\GatewayAccountResultDTO;
use App\DTOs\GatewayAccounts\UpdateGatewayAccountDTO;
use App\Models\GatewayAccount;
use App\PaymentGateways\PaymentGatewayManager;

class UpdateGatewayAccountAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = GatewayAccount::class;

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateGatewayAccountDTO) {
            throw new \InvalidArgumentException('UpdateGatewayAccountAction requires an UpdateGatewayAccountDTO.');
        }

        $dto = $input;
        $account = GatewayAccount::query()->findOrFail($dto->id);

        $updateData = array_filter([
            'name' => $dto->name,
            'description' => $dto->description,
            'settings' => $dto->settings,
            'invoicing_enabled' => $dto->invoicing_enabled,
        ], static fn (mixed $value): bool => $value !== null);

        $account->update($updateData);

        return ActionResultDTO::success(
            GatewayAccountResultDTO::fromModel($account->refresh(), $this->gatewayManager),
            'Conta de gateway atualizada com sucesso.',
        );
    }
}
