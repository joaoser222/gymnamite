<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Sale;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Model;

class SaleController extends Controller
{
    use HasModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'total', 'status', 'payment_method', 'created_at'];

    protected array $joins = ['client'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'sales.id',
        'total' => 'sales.total',
        'status' => 'sales.status',
        'payment_method' => 'sales.payment_method',
        'created_at' => 'sales.created_at',
        'client_name' => 'clients.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['client_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'total', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::SALE;
    }

    protected function modelClass(): string
    {
        return Sale::class;
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
                'clients' => $this->modelOptions(Client::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
            ],
        ];
    }
}
