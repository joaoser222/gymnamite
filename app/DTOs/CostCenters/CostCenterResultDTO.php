<?php

namespace App\DTOs\CostCenters;

use App\Models\CostCenter;
use Spatie\LaravelData\Data;

class CostCenterResultDTO extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public string $operation_type,
        public string $created_at,
    ) {}

    public static function fromModel(CostCenter $costCenter): static
    {
        return new static(
            id: $costCenter->id,
            name: $costCenter->name,
            color: $costCenter->color,
            operation_type: $costCenter->operation_type->value,
            created_at: $costCenter->created_at?->toISOString() ?? '',
        );
    }
}
