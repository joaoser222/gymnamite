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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Date;

class DirectLesson extends Model implements BillingInvoiceSource
{
    use HasVisibility;

    protected $table = 'direct_lessons';

    protected $fillable = [
        'lesson_date',
        'status',
        'payment_method',
        'price',
        'client_id',
        'trainer_id',
    ];

    protected $casts = [
        'price' => 'float',
        'lesson_date' => 'date:Y-m-d',
        'status' => BillableStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    protected $attributes = [
        'status' => BillableStatus::OPEN,
        'payment_method' => PaymentMethod::CASH,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable');
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
        return (float) $this->price;
    }

    public function billingDiscountValue(): float
    {
        return 0;
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
        return (float) $this->price;
    }

    public function billingInstallments(): int
    {
        return 1;
    }

    public function billingFirstDueDate(): ?CarbonInterface
    {
        return Date::today();
    }

    public function billingPaymentMethod(): PaymentMethod
    {
        return $this->payment_method ?? PaymentMethod::CASH;
    }

    public function billingAnnotations(): ?string
    {
        return null;
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
