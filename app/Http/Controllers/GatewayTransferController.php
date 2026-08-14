<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\GatewayTransfers\CreateGatewayTransferAction;
use App\Enums\Gateway\TransactionStatus;
use App\Models\GatewayTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayTransferController extends ReadOnlyModuleController
{
    public function __construct(private readonly CreateGatewayTransferAction $createGatewayTransfer) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_reference_key',
        'gross_value',
        'fee_value',
        'total',
        'status',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['gateway_reference_key', 'status'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'gross_value', 'fee_value', 'total', 'status', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_TRANSFER;
    }

    protected function modelClass(): string
    {
        return GatewayTransfer::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'transactionStatus' => $this->enumOptions(TransactionStatus::class),
            ],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $transfer = $this->createGatewayTransfer->execute($request->validate([
            'gateway_transfer_recipient_id' => ['required', 'integer', 'exists:gateway_transfer_recipients,id'],
            'value' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]));

        return response()->json($transfer, 201);
    }
}
