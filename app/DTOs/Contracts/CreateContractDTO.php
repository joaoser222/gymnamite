<?php

namespace App\DTOs\Contracts;

use App\Enums\GenderType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateContractDTO extends BaseDTO
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public int $plan_id,

        #[Required, IntegerType, Min(1)]
        public int $installments,

        #[Nullable, IntegerType]
        public ?int $client_id,

        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Email, Max(255)]
        public string $email,

        #[Required, StringType, Min(10), Max(11)]
        public string $phone,

        #[Required, StringType, Size(11)]
        public string $document,

        #[Required, Enum(GenderType::class)]
        public string $gender,

        #[Required, Date]
        public string $birth_date,

        #[BooleanType]
        public bool $legal_representative = false,

        #[Nullable, StringType, Max(255)]
        public ?string $legal_representative_name = null,

        #[Nullable, StringType, Size(11)]
        public ?string $legal_representative_document = null,

        #[Nullable, Date]
        public ?string $legal_representative_birth_date = null,

        #[Nullable, StringType, Max(8)]
        public ?string $address_postal_code = null,

        #[Nullable, StringType, Max(200)]
        public ?string $address = null,

        #[Nullable, StringType, Max(10)]
        public ?string $address_number = null,

        #[Nullable, StringType, Max(100)]
        public ?string $address_complement = null,

        #[Nullable, StringType, Max(100)]
        public ?string $address_district = null,

        #[Nullable, StringType, Size(2)]
        public ?string $address_state = null,

        #[Nullable, StringType, Max(100)]
        public ?string $address_city = null,

        #[Nullable, StringType, Max(255)]
        public ?string $coupon_code = null,

        #[Nullable, StringType, Max(500)]
        public ?string $annotations = null,

        #[BooleanType]
        public bool $accepted_terms = false,

        #[BooleanType]
        public bool $generate_invoices = true,
    ) {}
}
