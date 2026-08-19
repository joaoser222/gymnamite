<?php

namespace App\DTOs\PlanCategories;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreatePlanCategoryDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
    ) {}
}
