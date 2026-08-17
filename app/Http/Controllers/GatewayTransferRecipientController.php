<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Http\Requests\GatewayTransferRecipientRequest;
use App\Models\GatewayAccount;
use App\Models\GatewayTransferRecipient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class GatewayTransferRecipientController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_account_id',
        'label',
        'holder_name',
        'holder_document',
        'pix_key_type',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['label', 'holder_name', 'holder_document'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'label', 'holder_name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_TRANSFER_RECIPIENT;
    }

    protected function modelClass(): string
    {
        return GatewayTransferRecipient::class;
    }

    protected function storeRequestClass(): ?string
    {
        return GatewayTransferRecipientRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return GatewayTransferRecipientRequest::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'gatewayAccounts' => GatewayAccount::query()
                    ->where('visibility', 'visible')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (GatewayAccount $account): array => [
                        'value' => $account->id,
                        'label' => $account->name,
                    ])
                    ->all(),
            ],
        ];
    }
}
