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

class UpdateFinancialAccountDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $id,

        #[Nullable, StringType, Max(255)]
        public ?string $name = null,

        #[Nullable, Enum(FinancialAccountType::class)]
        public ?string $account_type = null,

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
