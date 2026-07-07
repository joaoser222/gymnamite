<?php

namespace App\Models;

use App\Contracts\BillingInvoiceSource;
use App\Enums\BillableStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Traits\HasVisibility;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model implements BillingInvoiceSource
{
    use HasVisibility;

    protected $table = 'contracts';

    protected $fillable = [
        'plan_name',
        'modality_quantity',
        'gross_value',
        'discount_value',
        'total',
        'payment_method',
        'first_due_date',
        'installments',
        'accepted_terms',
        'annotations',
        'plan_id',
        'plan_category_id',
        'client_id',
        'status',
    ];

    protected $casts = [
        'first_due_date' => 'date:Y-m-d',
        'gross_value' => 'float',
        'discount_value' => 'float',
        'total' => 'float',
        'status' => BillableStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    protected $attributes = [
        'status' => BillableStatus::OPEN,
        'discount_value' => 0,
        'payment_method' => PaymentMethod::CASH,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planCategory(): BelongsTo
    {
        return $this->belongsTo(PlanCategory::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function billingHolder(): Model
    {
        return $this->client;
    }

    public function billingOperationType(): OperationType
    {
        return OperationType::RECEIVABLE;
    }

    public function billingGrossValue(): float
    {
        return (float) ($this->gross_value ?? $this->total);
    }

    public function billingDiscountValue(): float
    {
        return (float) ($this->discount_value ?? 0);
    }

    public function billingTotalValue(): float
    {
        return (float) $this->total;
    }

    public function billingInstallments(): int
    {
        return (int) ($this->installments ?? 1);
    }

    public function billingFirstDueDate(): ?CarbonInterface
    {
        return $this->first_due_date;
    }

    public function billingPaymentMethod(): PaymentMethod
    {
        return $this->payment_method ?? PaymentMethod::CASH;
    }

    public function billingAnnotations(): ?string
    {
        return $this->annotations;
    }

    public function billingFinancialCategoryId(): ?int
    {
        return null;
    }

    public function billingFinancialAccountId(): ?int
    {
        return null;
    }
}
