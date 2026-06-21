<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Traits\HasMorphObjects;
use App\Traits\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasMorphObjects;
    use HasVisibility;

    protected $table = 'invoices';

    protected $fillable = [
        'operation_type',
        'invoice_type',
        'due_date',
        'payment_date',
        'payment_method',
        'external_reference',
        'gross_value',
        'discount_value',
        'interest_value',
        'fine_value',
        'total',
        'paid_value',
        'installment_number',
        'status',
        'annotations',
        'holder_id',
        'holder_type',
        'billable_id',
        'billable_type',
        'financial_account_id',
        'financial_category_id',
    ];

    protected $casts = [
        'due_date'  => 'date:Y-m-d',
        'payment_date'  => 'date:Y-m-d',
        'gross_value' => 'float',
        'discount_value' => 'float',
        'interest_value' => 'float',
        'fine_value' => 'float',
        'total' => 'float',
        'paid_value' => 'float',
        'operation_type' => OperationType::class,
        'status' => InvoiceStatus::class,
        'invoice_type' => InvoiceType::class,
        'payment_method' => PaymentMethod::class,
    ];

    protected $attributes = [
        'status' => InvoiceStatus::PENDING,
        'invoice_type' => InvoiceType::STANDARD,
        'payment_method' => PaymentMethod::CASH,

    ];

    protected $morphMaps = [
        'holder_type' => [
            'client' => Client::class,
            'supplier' => Supplier::class,
            'trainer' => Trainer::class,
        ],
        'billable_type' => [
            'contract' => Contract::class,
            'sale' => Sale::class,
            'purchase' => Purchase::class,
            'direct_lesson' => DirectLesson::class,
        ],
    ];

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function holder()
    {
        return $this->morphTo();
    }

    public function billable()
    {
        return $this->morphTo();
    }
}
