<?php

namespace App\DTOs\FinancialAccounts;

use App\DTOs\Contracts\BaseDTO;
use App\Enums\FinancialAccountType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateFinancialAccountDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Enum(FinancialAccountType::class)]
        public string $account_type,

        #[Nullable, StringType, Max(255)]
        public ?string $holder_name = null,

        #[Nullable, StringType, Max(20)]
        public ?string $holder_document = null,

        #[Nullable, Date]
        public ?string $holder_birth_date = null,

        #[Nullable, StringType, Max(50)]
        public ?string $bank_account_number = null,

        #[Nullable, StringType, Max(50)]
        public ?string $bank_agency = null,

        #[Nullable, StringType, Max(20)]
        public ?string $bank_account_type = null,

        #[Nullable, StringType, Max(20)]
        public ?string $bank_code = null,
    ) {}
}
