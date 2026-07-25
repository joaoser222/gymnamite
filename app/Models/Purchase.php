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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model implements BillingInvoiceSource
{
    use HasVisibility;

    protected $table = 'purchases';

    protected $fillable = [
        'total',
        'gross_value',
        'discount_value',
        'status',
        'payment_method',
        'annotations',
        'first_due_date',
        'installments',
        'disable_stock',
        'supplier_id',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'first_due_date' => 'date:Y-m-d',
        'gross_value' => 'float',
        'total' => 'float',
        'payment_method' => PaymentMethod::class,
    ];

    protected $attributes = [
        'status' => BillableStatus::OPEN,
        'installments' => 1,
        'payment_method' => PaymentMethod::CASH,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable');
    }

    public function billingHolder(): Model
    {
        return $this->supplier;
    }

    public function billingOperationType(): OperationType
    {
        return OperationType::PAYABLE;
    }

    public function billingGrossValue(): float
    {
        return (float) $this->gross_value;
    }

    public function billingDiscountValue(): float
    {
        return (float) $this->discount_value;
    }

    public function billingDiscountPercent(): ?float
    {
        return null;
    }

    public function billingDiscountLimit(): ?float
    {
        return null;
    }

    public function billingDiscountedInstallments(): ?int
    {
        return null;
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
