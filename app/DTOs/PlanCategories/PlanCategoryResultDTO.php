<?php

namespace App\DTOs\PlanCategories;

use App\Models\PlanCategory;
use Spatie\LaravelData\Data;

class PlanCategoryResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $created_at,
    ) {}

    public static function fromModel(PlanCategory $planCategory): static
    {
        return new static(
            id: $planCategory->id,
            name: $planCategory->name,
            created_at: $planCategory->created_at?->toISOString() ?? '',
        );
    }
}
