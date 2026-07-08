<?php

namespace App\Contracts;

use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

interface BillingInvoiceSource
{
    public function billingHolder(): Model;

    public function billingOperationType(): OperationType;

    public function billingGrossValue(): float;

    public function billingDiscountValue(): float;

    public function billingDiscountPercent(): ?float;

    public function billingDiscountLimit(): ?float;

    public function billingDiscountedInstallments(): ?int;

    public function billingTotalValue(): float;

    public function billingInstallments(): int;

    public function billingFirstDueDate(): ?CarbonInterface;

    public function billingPaymentMethod(): PaymentMethod;

    public function billingAnnotations(): ?string;

    public function billingFinancialCategoryId(): ?int;

    public function billingFinancialAccountId(): ?int;
}
