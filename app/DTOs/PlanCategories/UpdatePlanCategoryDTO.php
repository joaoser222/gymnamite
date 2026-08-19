<?php

namespace App\DTOs\PlanCategories;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class UpdatePlanCategoryDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Nullable, StringType, Max(255)]
        public ?string $name = null,
    ) {}
}
