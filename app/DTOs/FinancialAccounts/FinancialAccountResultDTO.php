<?php

namespace App\DTOs\FinancialAccounts;

use App\Models\FinancialAccount;
use Spatie\LaravelData\Data;

class FinancialAccountResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $account_type,
        public float $balance,
        public ?string $holder_name,
        public ?string $holder_document,
        public string $created_at,
    ) {}

    public static function fromModel(FinancialAccount $account): static
    {
        return new static(
            id: $account->id,
            name: $account->name,
            account_type: $account->account_type->value,
            balance: $account->balance,
            holder_name: $account->holder_name,
            holder_document: $account->holder_document,
            created_at: $account->created_at?->toISOString() ?? '',
        );
    }
}
