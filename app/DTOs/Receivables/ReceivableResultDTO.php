<?php

namespace App\DTOs\Receivables;

use App\Models\Receivable;
use Spatie\LaravelData\Data;

class ReceivableResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $due_date,
        public ?string $payment_date,
        public float $total,
        public string $status,
        public string $created_at,
        public ?int $holder_id = null,
        public ?string $holder_type = null,
        public ?int $gateway_payment_id = null,
        public ?int $gateway_invoice_id = null,
        public ?int $financial_account_id = null,
        public ?int $financial_category_id = null,
    ) {}

    public static function fromModel(Receivable $receivable): static
    {
        return new static(
            id: $receivable->id,
            due_date: $receivable->due_date?->format('Y-m-d') ?? '',
            payment_date: $receivable->payment_date?->format('Y-m-d'),
            total: $receivable->total,
            status: $receivable->status->value,
            created_at: $receivable->created_at?->toISOString() ?? '',
            holder_id: $receivable->holder_id,
            holder_type: $receivable->holder_type,
            gateway_payment_id: $receivable->gateway_payment_id,
            gateway_invoice_id: $receivable->gateway_invoice_id,
            financial_account_id: $receivable->financial_account_id,
            financial_category_id: $receivable->financial_category_id,
        );
    }
}
