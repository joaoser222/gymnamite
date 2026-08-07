<?php

namespace App\DTOs\Invoices;

use App\Models\Invoice;
use Spatie\LaravelData\Data;

class InvoiceResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $operation_type,
        public string $invoice_type,
        public string $due_date,
        public ?string $payment_date,
        public string $payment_method,
        public float $gross_value,
        public float $discount_value,
        public float $interest_value,
        public float $fine_value,
        public float $total,
        public float $paid_value,
        public int $installment_number,
        public string $status,
        public ?string $annotations,
        public ?int $holder_id,
        public ?string $holder_type,
        public ?int $billable_id,
        public ?string $billable_type,
        public ?int $financial_account_id,
        public ?int $financial_category_id,
        public string $created_at,
    ) {}

    public static function fromModel(Invoice $invoice): static
    {
        return new static(
            id: $invoice->id,
            operation_type: $invoice->operation_type->value,
            invoice_type: $invoice->invoice_type->value,
            due_date: $invoice->due_date?->format('Y-m-d') ?? '',
            payment_date: $invoice->payment_date?->format('Y-m-d'),
            payment_method: $invoice->payment_method->value,
            gross_value: $invoice->gross_value,
            discount_value: $invoice->discount_value,
            interest_value: $invoice->interest_value,
            fine_value: $invoice->fine_value,
            total: $invoice->total,
            paid_value: $invoice->paid_value,
            installment_number: $invoice->installment_number,
            status: $invoice->status->value,
            annotations: $invoice->annotations,
            holder_id: $invoice->holder_id,
            holder_type: $invoice->holder_type,
            billable_id: $invoice->billable_id,
            billable_type: $invoice->billable_type,
            financial_account_id: $invoice->financial_account_id,
            financial_category_id: $invoice->financial_category_id,
            created_at: $invoice->created_at?->toISOString() ?? '',
        );
    }
}
