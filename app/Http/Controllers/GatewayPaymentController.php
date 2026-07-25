<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessModule;
use App\Enums\Gateway\TransactionStatus;
use App\Enums\PaymentMethod;
use App\Models\GatewayPayment;
use App\Traits\HasReadOnlyModule;
use Illuminate\Http\Request;

class GatewayPaymentController extends Controller
{
    use HasReadOnlyModule;

    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_reference_key',
        'payment_method',
        'payment_date',
        'status',
        'gross_value',
        'fee_value',
        'total',
        'gateway_account_id',
        'gateway_customer_id',
        'invoice_id',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['gateway_reference_key', 'payment_method', 'status', 'payment_date'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'payment_date', 'status', 'gross_value', 'fee_value', 'total', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_PAYMENT;
    }

    protected function modelClass(): string
    {
        return GatewayPayment::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
                'transactionStatus' => $this->enumOptions(TransactionStatus::class),
            ],
        ];
    }
}
