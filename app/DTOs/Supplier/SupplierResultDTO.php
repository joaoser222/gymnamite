<?php

namespace App\DTOs\Supplier;

use App\Models\Supplier;
use Spatie\LaravelData\Data;

class SupplierResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public string $document,
        public ?string $phone,
        public string $created_at,
    ) {}

    public static function fromModel(Supplier $supplier): static
    {
        return new static(
            id: $supplier->id,
            name: $supplier->name,
            email: $supplier->email,
            document: $supplier->document,
            phone: $supplier->phone,
            created_at: $supplier->created_at?->toISOString() ?? '',
        );
    }
}
