<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Payable;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class PayableController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'due_date', 'payment_date', 'total', 'status', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['id'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'due_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PAYABLE;
    }

    protected function modelClass(): string
    {
        return Payable::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
                'financialAccounts' => $this->modelOptions(FinancialAccount::class),
                'financialCategories' => $this->modelOptions(FinancialCategory::class),
            ],
        ];
    }
}
