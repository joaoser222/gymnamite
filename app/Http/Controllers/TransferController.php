<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Models\FinancialAccount;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Model;

class TransferController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'value', 'status', 'annotations', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['annotations'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::TRANSFER;
    }

    protected function modelClass(): string
    {
        return Transfer::class;
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'financialAccounts' => $this->modelOptions(FinancialAccount::class),
            ],
        ];
    }
}
