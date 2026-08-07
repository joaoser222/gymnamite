<?php

namespace App\DTOs\Invoices;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\StringType;

class FiscalSyncDTO extends BaseDTO
{
    public function __construct(
        #[ArrayType(Integer::class, min(1))]
        public array $account_ids = [],

        #[ArrayType(StringType::class)]
        public array $statuses = [],

        #[Boolean]
        public bool $force = false,
    ) {}
}
