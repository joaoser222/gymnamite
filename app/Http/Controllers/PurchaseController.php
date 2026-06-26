<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class PurchaseController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'total', 'status', 'payment_method', 'created_at'];

    protected array $joins = ['supplier'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'purchases.id',
        'total' => 'purchases.total',
        'status' => 'purchases.status',
        'payment_method' => 'purchases.payment_method',
        'created_at' => 'purchases.created_at',
        'supplier_name' => 'suppliers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['supplier_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'total', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PURCHASE;
    }

    protected function modelClass(): string
    {
        return Purchase::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'suppliers' => $this->modelOptions(Supplier::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }
}
