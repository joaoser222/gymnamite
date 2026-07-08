<?php

namespace App\Services;

use App\Contracts\BillingInvoiceSource;
use App\Enums\InvoiceStatus;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class BillingInvoiceService
{
    public function generate(BillingInvoiceSource&Model $source): Collection
    {
        $installments = $source->billingInstallments();
        $firstDueDate = $source->billingFirstDueDate();

        if ($installments < 1) {
            throw new InvalidArgumentException('Billing installments must be greater than zero.');
        }

        if ($firstDueDate === null) {
            throw new InvalidArgumentException('Billing first due date is required to generate invoices.');
        }

        $source->loadMissing($this->sourceRelations($source));

        $grossValueInstallments = $this->splitAmount(
            $source->billingGrossValue(),
            $installments,
        );

        $discountValueInstallments = $this->resolveDiscountInstallments(
            $source,
            $grossValueInstallments,
        );

        $holder = $source->billingHolder();
        $baseDueDate = CarbonImmutable::instance($firstDueDate);
        $invoices = new Collection;

        for ($installmentNumber = 1; $installmentNumber <= $installments; $installmentNumber++) {
            $invoice = new Invoice;
            $invoice->fill([
                'operation_type' => $source->billingOperationType(),
                'due_date' => $baseDueDate->addMonthsNoOverflow($installmentNumber - 1)->format('Y-m-d'),
                'payment_method' => $source->billingPaymentMethod(),
                'gross_value' => $grossValueInstallments[$installmentNumber - 1],
                'discount_value' => $discountValueInstallments[$installmentNumber - 1],
                'interest_value' => 0,
                'fine_value' => 0,
                'paid_value' => 0,
                'installment_number' => $installmentNumber,
                'status' => InvoiceStatus::PENDING,
                'annotations' => $source->billingAnnotations(),
                'financial_account_id' => $source->billingFinancialAccountId(),
                'financial_category_id' => $source->billingFinancialCategoryId(),
                'visibility' => 'visible',
            ]);
            $invoice->holder()->associate($holder);
            $invoice->billable()->associate($source);
            $invoice->save();

            $invoices->push($invoice);
        }

        return $invoices;
    }

    /**
     * @param  array<int, float>  $grossValueInstallments
     * @return array<int, float>
     */
    public function resolveDiscountInstallments(
        BillingInvoiceSource $source,
        array $grossValueInstallments,
    ): array {
        $discountPercent = $source->billingDiscountPercent();
        $discountedInstallments = $source->billingDiscountedInstallments();

        if ($discountPercent === null || $discountedInstallments === null) {
            return $this->splitAmount(
                $source->billingDiscountValue(),
                count($grossValueInstallments),
            );
        }

        $eligibleInstallments = min(count($grossValueInstallments), max(0, $discountedInstallments));
        $discounts = array_fill(0, count($grossValueInstallments), 0.0);

        if ($eligibleInstallments === 0 || $discountPercent <= 0) {
            return $discounts;
        }

        $rawEligibleDiscounts = [];

        for ($index = 0; $index < $eligibleInstallments; $index++) {
            $rawEligibleDiscounts[] = round(
                $grossValueInstallments[$index] * ($discountPercent / 100),
                4,
            );
        }

        $resolvedEligibleDiscounts = $this->applyDiscountLimit(
            $rawEligibleDiscounts,
            $source->billingDiscountLimit(),
        );

        foreach ($resolvedEligibleDiscounts as $index => $discountValue) {
            $discounts[$index] = $discountValue;
        }

        return $discounts;
    }

    /**
     * @return array<int, string>
     */
    private function splitAmount(float $amount, int $installments): array
    {
        $scale = 10000;
        $total = (int) round($amount * $scale);
        $baseInstallmentValue = intdiv($total, $installments);
        $remainder = $total % $installments;
        $parts = [];

        for ($index = 0; $index < $installments; $index++) {
            $parts[] = ($baseInstallmentValue + ($index < $remainder ? 1 : 0)) / $scale;
        }

        return $parts;
    }

    /**
     * @param  array<int, float>  $discounts
     * @return array<int, float>
     */
    private function applyDiscountLimit(array $discounts, ?float $discountLimit): array
    {
        if ($discountLimit === null || $discountLimit <= 0) {
            return $discounts;
        }

        $rawDiscountTotal = array_sum($discounts);

        if ($rawDiscountTotal <= $discountLimit) {
            return $discounts;
        }

        return $this->splitAmount($discountLimit, count($discounts));
    }

    /**
     * @return array<int, string>
     */
    private function sourceRelations(Model $source): array
    {
        return match ($source::class) {
            Contract::class => ['client', 'coupon'],
            Sale::class => ['client'],
            Purchase::class => ['supplier'],
            default => [],
        };
    }
}
