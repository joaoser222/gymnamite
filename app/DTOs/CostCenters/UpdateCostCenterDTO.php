<?php

namespace App\DTOs\CostCenters;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\OperationType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdateCostCenterDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Nullable, StringType, Max(255)]
        public ?string $name = null,

        #[Nullable, StringType, Max(7)]
        public ?string $color = null,

        #[Nullable, Enum(OperationType::class)]
        public ?string $operation_type = null,
    ) {}
}
