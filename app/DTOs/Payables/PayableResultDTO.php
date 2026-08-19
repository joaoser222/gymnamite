<?php

namespace App\DTOs\Payables;

use App\Models\Payable;
use Spatie\LaravelData\Data;

class PayableResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $due_date,
        public ?string $payment_date,
        public float $total,
        public string $status,
        public string $created_at,
        public int $supplier_id,
        public string $supplier_name,
        public ?int $financial_account_id = null,
        public ?int $financial_category_id = null,
    ) {}

    public static function fromModel(Payable $payable): static
    {
        return new static(
            id: $payable->id,
            due_date: $payable->due_date?->format('Y-m-d') ?? '',
            payment_date: $payable->payment_date?->format('Y-m-d'),
            total: $payable->total,
            status: $payable->status->value,
            created_at: $payable->created_at?->toISOString() ?? '',
            supplier_id: $payable->holder_id,
            supplier_name: $payable->holder?->name ?? '',
            financial_account_id: $payable->financial_account_id,
            financial_category_id: $payable->financial_category_id,
        );
    }
}
