<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Receivable;
use App\Traits\HasModule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReceivableController extends Controller
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
        return AccessModule::RECEIVABLE;
    }

    protected function modelClass(): string
    {
        return Receivable::class;
    }

    protected function newModelQuery(): Builder
    {
        return Receivable::query()->where('operation_type', OperationType::RECEIVABLE->value);
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
            ],
        ];
    }
}
