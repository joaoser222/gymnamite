<?php

namespace App\DTOs\GatewayAccounts;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateGatewayAccountDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Nullable, StringType, Max(500)]
        public ?string $description = null,

        #[Required, ArrayType]
        public array $settings = [],

        #[BooleanType]
        public bool $invoicing_enabled = false,
    ) {}
}
