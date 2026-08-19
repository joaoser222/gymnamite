<?php

namespace App\DTOs\Supplier;

use App\DTOs\Contracts\BaseDTO;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class CreateSupplierDTO extends BaseDTO
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Nullable, Email, Max(255)]
        public ?string $email = null,

        #[Required, StringType, Max(20)]
        public string $document,

        #[Nullable, StringType, Max(20)]
        public ?string $phone = null,

        #[Nullable, StringType, Max(255)]
        public ?string $address = null,

        #[Nullable, StringType, Max(50)]
        public ?string $address_number = null,

        #[Nullable, StringType, Max(255)]
        public ?string $address_complement = null,

        #[Nullable, StringType, Max(2)]
        public ?string $address_state = null,

        #[Nullable, StringType, Max(255)]
        public ?string $address_city = null,

        #[Nullable, StringType, Max(255)]
        public ?string $address_district = null,

        #[Nullable, StringType, Max(10)]
        public ?string $address_postal_code = null,
    ) {}
}
