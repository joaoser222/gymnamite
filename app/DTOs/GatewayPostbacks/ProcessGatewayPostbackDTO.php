<?php

namespace App\DTOs\GatewayPostbacks;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;

class ProcessGatewayPostbackDTO extends BaseDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        #[IntegerType]
        public int $gateway_account_id,

        #[ArrayType]
        public array $payload,
    ) {}
}
