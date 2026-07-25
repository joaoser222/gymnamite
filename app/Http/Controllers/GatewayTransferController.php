<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\Gateway\TransactionStatus;
use App\Models\GatewayTransfer;
use App\Traits\HasReadOnlyModule;
use Illuminate\Http\Request;

class GatewayTransferController extends Controller
{
    use HasReadOnlyModule;

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
}
