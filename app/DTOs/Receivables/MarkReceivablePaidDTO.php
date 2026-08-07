<?php

namespace App\DTOs\Receivables;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class MarkReceivablePaidDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType]
        public int $id,

        #[Required, StringType, Date]
        public string $payment_date,
    ) {}
}
