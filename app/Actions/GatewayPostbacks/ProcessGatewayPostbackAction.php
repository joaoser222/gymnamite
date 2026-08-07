<?php

namespace App\Actions\GatewayPostbacks;

use App\Actions\BaseAction;
use App\DTOs\GatewayPostbacks\ProcessGatewayPostbackDTO;
use App\Models\GatewayAccount;
use App\Models\GatewayPostback;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use Symfony\Component\HttpFoundation\Response;

class ProcessGatewayPostbackAction extends BaseAction
{
    /** Module access is verified by the gateway webhook token. */
    protected string $ability = '';

    protected string $modelClass = GatewayPostback::class;

    protected function handle(mixed $input): GatewayPostback
    {
        if (! $input instanceof ProcessGatewayPostbackDTO) {
            throw new \InvalidArgumentException('ProcessGatewayPostbackAction requires a ProcessGatewayPostbackDTO.');
        }

        $gatewayAccount = GatewayAccount::query()->findOrFail($input->gateway_account_id);

        return match ($gatewayAccount->name) {
            'Asaas' => app(AsaasPaymentGatewayAdapter::class, ['gatewayAccount' => $gatewayAccount])
                ->processPostback($input->payload),
            default => abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Gateway provider is not supported.'),
        };
    }
}
