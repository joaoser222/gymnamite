<?php

namespace App\DTOs\FinancialCategories;

use App\Models\FinancialCategory;
use Spatie\LaravelData\Data;

class FinancialCategoryResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public string $operation_type,
        public ?int $cost_center_id,
        public string $created_at,
    ) {}

    public static function fromModel(FinancialCategory $category): static
    {
        return new static(
            id: $category->id,
            name: $category->name,
            color: $category->color,
            operation_type: $category->operation_type->value,
            cost_center_id: $category->cost_center_id,
            created_at: $category->created_at?->toISOString() ?? '',
        );
    }
}
