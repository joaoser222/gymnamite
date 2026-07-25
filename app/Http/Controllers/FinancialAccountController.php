<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\FinancialAccountType;
use App\Http\Requests\FinancialAccountRequest;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FinancialAccountController extends CrudModuleController
{
    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'account_type', 'balance', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::FINANCIAL_ACCOUNT;
    }

    protected function modelClass(): string
    {
        return FinancialAccount::class;
    }

    protected function storeRequestClass(): ?string
    {
        return FinancialAccountRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return FinancialAccountRequest::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'accountTypes' => $this->enumOptions(FinancialAccountType::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'accountTypes' => $this->enumOptions(FinancialAccountType::class),
            ],
        ];
    }
}
