<?php

namespace App\DTOs\FinancialCategories;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\OperationType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateFinancialCategoryDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Nullable, StringType, Max(7)]
        public ?string $color = null,

        #[Required, Enum(OperationType::class)]
        public string $operation_type,

        #[Nullable, IntegerType, Min(1)]
        public ?int $cost_center_id = null,
    ) {}
}
