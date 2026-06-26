<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class FinancialAccountController extends Controller
{
    use HasModule;

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

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'accountTypes' => $this->enumOptions(FinancialAccountType::class),
            ],
        ];
    }
}
