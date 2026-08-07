<?php

namespace App\DTOs\Receivables;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;

class RequestGatewayInvoiceDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType]
        public int $id,
    ) {}
}
