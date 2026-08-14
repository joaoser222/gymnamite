<?php

namespace App\Actions\GatewayTransfers;

use App\Actions\BaseAction;
use App\Models\GatewayTransfer;
use App\Models\GatewayTransferRecipient;
use App\Services\Gateway\GatewayAdapterResolver;

class CreateGatewayTransferAction extends BaseAction
{
    public function __construct(private readonly GatewayAdapterResolver $adapterResolver) {}

    protected function handle(mixed $input): GatewayTransfer
    {
        $recipient = GatewayTransferRecipient::query()
            ->with('gatewayAccount')
            ->where('visibility', 'visible')
            ->findOrFail($input['gateway_transfer_recipient_id']);

        return $this->adapterResolver->paymentAdapter($recipient->gatewayAccount)->createTransfer([
            'value' => $input['value'],
            'description' => $input['description'] ?? null,
            'pix_address_key' => $recipient->pix_key,
            'pix_address_key_type' => $recipient->pix_key_type,
            'gateway_transfer_recipient_id' => $recipient->id,
        ]);
    }
}
