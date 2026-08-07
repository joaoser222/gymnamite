<?php

namespace App\Services\Billing;

use App\Contracts\BillingInvoiceSource;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class InvoiceGenerator
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly DiscountCalculator $discountCalculator,
        private readonly InstallmentSplitter $installmentSplitter,
        private readonly BillingSourceResolver $sourceResolver,
    ) {}

    /**
     * Generate invoices for a billing source.
     *
     * @return Collection<int, Invoice>
     */
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

        $source->loadMissing($this->sourceResolver->sourceRelations($source));

        $grossValueInstallments = $this->installmentSplitter->split(
            $source->billingGrossValue(),
            $installments,
        );

        $discountValueInstallments = $this->discountCalculator->calculate(
            $source,
            $grossValueInstallments,
        );

        $holder = $source->billingHolder();
        $baseDueDate = CarbonImmutable::instance($firstDueDate);
        $invoices = new Collection;

        for ($installmentNumber = 1; $installmentNumber <= $installments; $installmentNumber++) {
            $invoice = $this->invoiceRepository->create([
                'operation_type' => $source->billingOperationType()->value,
                'due_date' => $baseDueDate->addMonthsNoOverflow($installmentNumber - 1)->format('Y-m-d'),
                'payment_method' => $source->billingPaymentMethod()->value,
                'gross_value' => $grossValueInstallments[$installmentNumber - 1],
                'discount_value' => $discountValueInstallments[$installmentNumber - 1],
                'interest_value' => 0,
                'fine_value' => 0,
                'paid_value' => 0,
                'installment_number' => $installmentNumber,
                'status' => InvoiceStatus::PENDING->value,
                'annotations' => $source->billingAnnotations(),
                'financial_account_id' => $source->billingFinancialAccountId(),
                'financial_category_id' => $source->billingFinancialCategoryId(),
                'visibility' => 'visible',
                'holder_id' => $holder->getKey(),
                'holder_type' => get_class($holder),
                'billable_id' => $source->getKey(),
                'billable_type' => get_class($source),
            ]);

            $invoices->push($invoice);
        }

        return $invoices;
    }
}
